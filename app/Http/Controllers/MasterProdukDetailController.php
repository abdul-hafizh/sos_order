<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Category;
use App\Models\MasterBerat;
use App\Models\MasterKarakter;
use App\Models\MasterProduk;
use App\Models\MasterProdukDetail;
use App\Models\MasterProdukDetailGambar;
use App\Models\MasterSatuan;
use App\Models\MasterTipe;
use App\Models\MasterUkuran;
use App\Models\MasterUom;
use App\Models\MasterWarna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterProdukDetailController extends Controller
{
    public function index(Request $request)
    {
        $produkDetail = MasterProdukDetail::with(['produk', 'category', 'tipe', 'satuan', 'berat', 'ukuran', 'warna', 'karakter', 'uom', 'gambars', 'barang'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_barang', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($q2) use ($search) {
                            $q2->where('nama_produk', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id_produk_detail')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Produk-Detail/Index', [
            'produkDetail' => $produkDetail,
            'filters' => $request->only(['search', 'per_page']),
            'list_produk' => MasterProduk::orderBy('nama_produk')->get(['id_produk', 'nama_produk', 'deskripsi']),
            'list_kategori' => Category::orderBy('categoryname')->get(['categorycode', 'categoryname']),
            'list_tipe' => MasterTipe::orderBy('nama')->get(),
            'list_satuan' => MasterSatuan::orderBy('nama')->get(),
            'list_berat' => MasterBerat::orderBy('nama')->get(),
            'list_ukuran' => MasterUkuran::orderBy('nama')->get(),
            'list_warna' => MasterWarna::orderBy('nama')->get(),
            'list_karakter' => MasterKarakter::orderBy('nama')->get(),
            'list_uom' => MasterUom::orderBy('nama_uom')->get(),
        ]);
    }

    public function barangOptions(Request $request)
    {
        $usedKodeBarang = MasterProdukDetail::whereNotNull('kode_barang')
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
            'id_produk' => 'required|integer|exists:master_produk,id_produk',
            'category_id' => 'nullable|string|max:20|exists:m_category,categorycode',
            'kode_barang' => 'nullable|string|max:50|unique:master_produk_detail,kode_barang',
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',
            'id_uom' => 'nullable|integer|exists:master_uom,id_uom',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $produkDetail = MasterProdukDetail::create($validated);

            if ($request->hasFile('foto')) {
                foreach ($request->file('foto') as $file) {
                    $path = $file->store('produk', 'public');

                    MasterProdukDetailGambar::create([
                        'id_produk_detail' => $produkDetail->id_produk_detail,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                    ]);
                }
            }
        });

        return back()->with('success', 'Produk detail berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $produkDetail = MasterProdukDetail::findOrFail($id);

        $validated = $request->validate([
            'id_produk' => 'required|integer|exists:master_produk,id_produk',
            'category_id' => 'nullable|string|max:20|exists:m_category,categorycode',
            'kode_barang' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('master_produk_detail', 'kode_barang')->ignore($produkDetail->id_produk_detail, 'id_produk_detail'),
            ],
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',
            'id_uom' => 'nullable|integer|exists:master_uom,id_uom',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'deleted_gambar_ids' => 'nullable|array',
            'deleted_gambar_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $validated, $produkDetail) {
            $produkDetail->update(collect($validated)->except(['foto', 'deleted_gambar_ids'])->all());

            $deletedGambarIds = $validated['deleted_gambar_ids'] ?? [];

            if (!empty($deletedGambarIds)) {
                $deletedGambars = MasterProdukDetailGambar::where('id_produk_detail', $produkDetail->id_produk_detail)
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

                    MasterProdukDetailGambar::create([
                        'id_produk_detail' => $produkDetail->id_produk_detail,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                    ]);
                }
            }
        });

        return back()->with('success', 'Produk detail berhasil diupdate');
    }

    public function destroy($id)
    {
        $produkDetail = MasterProdukDetail::with('gambars')->findOrFail($id);

        DB::transaction(function () use ($produkDetail) {
            foreach ($produkDetail->gambars as $gambar) {
                if (!empty($gambar->path_file)) {
                    Storage::disk('public')->delete($gambar->path_file);
                }

                $gambar->delete();
            }

            $produkDetail->delete();
        });

        return back()->with('success', 'Produk detail berhasil dihapus');
    }
}
