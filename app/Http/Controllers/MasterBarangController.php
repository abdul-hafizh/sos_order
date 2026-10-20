<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangVendor;
use App\Models\MasterProdukDetail;
use App\Services\BarangSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class MasterBarangController extends Controller
{
    public function index(Request $request)
    {
        $barangs = Barang::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%");
                });
            })
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Barang/Index', [
            'barangs' => $barangs,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|unique:t_barang,kode_barang',
            'nama_barang' => 'required|string|max:255',

            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'harga_jual_jumbo' => 'nullable|numeric|min:0',

            'margin' => 'nullable|numeric',

            'satuan' => 'required|string|max:50',
            'satuan_pos' => 'nullable|string|max:50',

            'qty_pos' => 'nullable|integer|min:0',

            'stok' => 'nullable|integer|min:0',

            'min_vendor' => 'nullable|integer|min:0',
            'min_cabang' => 'nullable|integer|min:0',
            'min_stok' => 'nullable|integer|min:0',
            'max_stok' => 'nullable|integer|min:0',

            'category_code' => 'nullable',

            'kirim_langsung' => 'boolean',
            'active' => 'boolean',
        ]);

        if ($validated['harga_jual'] < $validated['harga_beli']) {
            return back()
                ->withErrors([
                    'harga_jual' => 'Harga jual tidak boleh lebih kecil dari harga beli.'
                ])
                ->withInput();
        }

        if (
            !is_null($validated['harga_jual_jumbo']) &&
            $validated['harga_jual_jumbo'] < $validated['harga_beli']
        ) {
            return back()
                ->withErrors([
                    'harga_jual_jumbo' => 'Harga jual jumbo tidak boleh lebih kecil dari harga beli.'
                ])
                ->withInput();
        }

        Barang::create($validated);
        return back()->with('success', 'Barang berhasil ditambahkan');
    }

    public function update(Request $request, $id, BarangSyncService $syncService)
    {
        $barang = Barang::findOrFail($id);

        $validated = $request->validate([
            'kode_barang' => [
                'required',
                Rule::unique('t_barang', 'kode_barang')
                    ->ignore($barang->id_barang, 'id_barang'),
            ],

            'nama_barang' => 'required|string|max:255',

            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'harga_jual_jumbo' => 'nullable|numeric|min:0',

            'margin' => 'nullable|numeric',

            'satuan' => 'required|string|max:50',
            'satuan_pos' => 'nullable|string|max:50',

            'qty_pos' => 'nullable|integer|min:0',

            'stok' => 'nullable|integer|min:0',

            'min_vendor' => 'nullable|integer|min:0',
            'min_cabang' => 'nullable|integer|min:0',
            'min_stok' => 'nullable|integer|min:0',
            'max_stok' => 'nullable|integer|min:0',

            'category_code' => 'nullable',

            'kirim_langsung' => 'boolean',
            'active' => 'boolean',
        ]);

        if ($validated['harga_jual'] < $validated['harga_beli']) {
            return back()
                ->withErrors([
                    'harga_jual' => 'Harga jual tidak boleh lebih kecil dari harga beli.'
                ])
                ->withInput();
        }

        if (
            !is_null($validated['harga_jual_jumbo']) &&
            $validated['harga_jual_jumbo'] < $validated['harga_beli']
        ) {
            return back()
                ->withErrors([
                    'harga_jual_jumbo' => 'Harga jual jumbo tidak boleh lebih kecil dari harga beli.'
                ])
                ->withInput();
        }

        DB::transaction(function () use ($barang, $validated, $syncService) {
            $kodeBarangLama = $barang->kode_barang;

            $barang->update($validated);

            // Kode barang diganti: ikutkan relasi yang menempel lewat kode_barang.
            if ($kodeBarangLama !== $barang->kode_barang) {
                MasterProdukDetail::where('kode_barang', $kodeBarangLama)->update(['kode_barang' => $barang->kode_barang]);
                BarangVendor::where('kode_barang', $kodeBarangLama)->update(['kode_barang' => $barang->kode_barang]);
            }

            // Samakan harga, nama & kode di t_spk (+ t_spk_log), lalu m_item.
            $produkDetail = MasterProdukDetail::where('kode_barang', $barang->kode_barang)->first();

            $syncService->syncSpk($barang, $produkDetail);

            if ($produkDetail) {
                $syncService->syncMItemIfEligible($barang, $produkDetail);
            }
        });

        return back()->with('success', 'Barang berhasil diupdate');
    }
    public function destroy($id)
    {
        Barang::findOrFail($id)->delete();
        return back()->with('success', 'Barang berhasil dihapus');
    }
}