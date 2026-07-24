<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\MasterBerat;
use App\Models\MasterKarakter;
use App\Models\MasterProduk;
use App\Models\MasterProdukGambar;
use App\Models\MasterSatuan;
use App\Models\MasterTipe;
use App\Models\MasterUkuran;
use App\Models\MasterWarna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterProdukController extends Controller
{
    public function index(Request $request)
    {
        $produk = MasterProduk::with(['tipe', 'satuan', 'berat', 'ukuran', 'warna', 'karakter', 'gambars', 'barang'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id_produk')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Produk/Index', [
            'produk' => $produk,
            'filters' => $request->only(['search', 'per_page']),
            'list_tipe' => MasterTipe::orderBy('nama')->get(),
            'list_satuan' => MasterSatuan::orderBy('nama')->get(),
            'list_berat' => MasterBerat::orderBy('nama')->get(),
            'list_ukuran' => MasterUkuran::orderBy('nama')->get(),
            'list_warna' => MasterWarna::orderBy('nama')->get(),
            'list_karakter' => MasterKarakter::orderBy('nama')->get(),
        ]);
    }

    public function barangOptions(Request $request)
    {
        $usedKodeBarang = MasterProduk::whereNotNull('kode_barang')
            ->when($request->current, fn($q, $current) => $q->where('kode_barang', '!=', $current))
            ->pluck('kode_barang');

        $barangs = Barang::query()
            ->whereNotIn('kode_barang', $usedKodeBarang)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_barang')
            ->limit(20)
            ->get(['id_barang', 'kode_barang', 'nama_barang']);

        return response()->json($barangs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kode_barang' => 'nullable|string|max:50|unique:master_produk,kode_barang',
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $produk = MasterProduk::create($validated);

            if ($request->hasFile('foto')) {
                foreach ($request->file('foto') as $file) {
                    $path = $file->store('produk', 'public');

                    MasterProdukGambar::create([
                        'id_produk' => $produk->id_produk,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                    ]);
                }
            }
        });

        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $produk = MasterProduk::findOrFail($id);

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kode_barang' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('master_produk', 'kode_barang')->ignore($produk->id_produk, 'id_produk'),
            ],
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'deleted_gambar_ids' => 'nullable|array',
            'deleted_gambar_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $validated, $produk) {
            $produk->update(collect($validated)->except(['foto', 'deleted_gambar_ids'])->all());

            $deletedGambarIds = $validated['deleted_gambar_ids'] ?? [];

            if (!empty($deletedGambarIds)) {
                $deletedGambars = MasterProdukGambar::where('id_produk', $produk->id_produk)
                    ->whereIn('id_produk_gambar', $deletedGambarIds)
                    ->get();

                foreach ($deletedGambars as $gambar) {
                    if (!empty($gambar->path_file)) {
                        Storage::disk('public')->delete($gambar->path_file);
                    }

                    $gambar->delete();
                }
            }

            if ($request->hasFile('foto')) {
                foreach ($request->file('foto') as $file) {
                    $path = $file->store('produk', 'public');

                    MasterProdukGambar::create([
                        'id_produk' => $produk->id_produk,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                    ]);
                }
            }
        });

        return back()->with('success', 'Produk berhasil diupdate');
    }

    public function destroy($id)
    {
        $produk = MasterProduk::with('gambars')->findOrFail($id);

        DB::transaction(function () use ($produk) {
            foreach ($produk->gambars as $gambar) {
                if (!empty($gambar->path_file)) {
                    Storage::disk('public')->delete($gambar->path_file);
                }

                $gambar->delete();
            }

            $produk->delete();
        });

        return back()->with('success', 'Produk berhasil dihapus');
    }
}
