<?php

namespace App\Http\Controllers;

use App\Models\MasterProduk;
use App\Services\BarangSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MasterProdukController extends Controller
{
    public function index(Request $request)
    {
        $produk = MasterProduk::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id_produk')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Produk/Index', [
            'produk' => $produk,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        MasterProduk::create($validated);

        return back()->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, $id, BarangSyncService $syncService)
    {
        $produk = MasterProduk::findOrFail($id);

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        DB::transaction(function () use ($produk, $validated, $syncService) {
            $produk->update($validated);

            // Nama produk diteruskan ke nama barang di t_barang, t_spk & m_item.
            if ($produk->wasChanged('nama_produk')) {
                $syncService->syncFromProduk($produk);
            }
        });

        return back()->with('success', 'Produk berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterProduk::findOrFail($id)->delete();

        return back()->with('success', 'Produk berhasil dihapus');
    }
}
