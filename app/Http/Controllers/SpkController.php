<?php

namespace App\Http\Controllers;

use App\Models\Spk;
use App\Models\Barang;
use App\Models\BarangDetail;
use App\Models\BarangGambar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SpkController extends Controller
{
    public function index(Request $request)
    {
        $spks = Spk::query()
            ->with(['barang.details.gambars'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%")
                        ->orWhere('kode_cabang', 'like', "%{$search}%")
                        ->orWhere('period', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status_validasi'), function ($query) use ($request) {
                $query->where('status_validasi', $request->status_validasi);
            })
            ->when($request->filled('status_kirim_barang'), function ($query) use ($request) {
                $query->where('status_kirim_barang', $request->status_kirim_barang);
            })
            ->orderByDesc('id_po')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Spk/Index', [
            'spks' => $spks,
            'filters' => $request->only([
                'search',
                'per_page',
                'status_validasi',
                'status_kirim_barang',
            ]),
        ]);
    }

    public function show($id)
    {
        $spk = Spk::findOrFail($id);

        return Inertia::render('Spk/Show', [
            'spk' => $spk,
        ]);
    }

    public function buatMasterBarang(Request $request, $id)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:50|unique:t_barang,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'harga_beli' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'satuan' => 'nullable|string|max:50',
            'stok' => 'nullable|integer|min:0',
        ]);

        $spk = Spk::findOrFail($id);

        if ($spk->id_barang || $spk->kode_barang) {
            return back()->with('error', 'SPK ini sudah memiliki master barang.');
        }

        DB::transaction(function () use ($validated, $spk) {
            $barang = Barang::create([
                'kode_barang' => $validated['kode_barang'],
                'nama_barang' => $validated['nama_barang'],
                'harga_beli' => $validated['harga_beli'] ?? 0,
                'harga_jual' => $validated['harga_jual'] ?? 0,
                'satuan' => $validated['satuan'] ?? $spk->satuan,
                'satuan_pos' => $validated['satuan'] ?? $spk->satuan,
                'qty_pos' => 1,
                'stok' => $validated['stok'] ?? 0,
                'active' => 1,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);

            $detail = BarangDetail::create([
                'id_barang' => $barang->id_barang,
                'nama_variant' => 'Default',
                'kode_variant' => null,
                'harga_beli' => $barang->harga_beli,
                'harga_jual' => $barang->harga_jual,
                'harga_jual_jumbo' => null,
                'stok' => $barang->stok ?? 0,
                'active' => 1,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);

            if ($spk->gambar_permintaan && Storage::disk('public')->exists($spk->gambar_permintaan)) {
                $extension = pathinfo($spk->gambar_permintaan, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'barang/' . uniqid('spk_', true) . '.' . $extension;

                Storage::disk('public')->copy($spk->gambar_permintaan, $newPath);

                BarangGambar::create([
                    'id_barang_detail' => $detail->id_barang_detail,
                    'nama_file' => basename($newPath),
                    'path_file' => $newPath,
                    'active' => 1,
                    'modified_by' => auth()->id(),
                    'modified_date' => now(),
                ]);
            }

            $spk->update([
                'id_barang' => $barang->id_barang,
                'kode_barang' => $barang->kode_barang,
                'harga_beli' => $barang->harga_beli,
                'harga_jual' => $barang->harga_jual,
                'satuan' => $barang->satuan,
                'satuan_pos' => $barang->satuan_pos,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);
        });

        return back()->with('success', 'Master barang berhasil dibuat dari SPK.');
    }

    public function updateValidasi(Request $request, $id)
    {
        $validated = $request->validate([
            'status_validasi' => 'required|integer|in:0,1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $spk = Spk::findOrFail($id);

        $spk->update([
            'status_validasi' => $validated['status_validasi'],
            'tgl_validasi' => now(),
            'keterangan' => $validated['keterangan'] ?? $spk->keterangan,
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);

        return back()->with('success', 'Status validasi SPK berhasil diperbarui.');
    }

    public function updateKirim(Request $request, $id)
    {
        $validated = $request->validate([
            'status_kirim_barang' => 'required|integer|in:0,1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $spk = Spk::findOrFail($id);

        $spk->update([
            'status_kirim_barang' => $validated['status_kirim_barang'],
            'tgl_kirim_barang' => $validated['status_kirim_barang'] == 1 ? now() : null,
            'keterangan' => $validated['keterangan'] ?? $spk->keterangan,
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);

        return back()->with('success', 'Status kirim barang berhasil diperbarui.');
    }

    public function updateTerima(Request $request, $id)
    {
        $validated = $request->validate([
            'status_terima_barang' => 'required|integer|in:0,1',
            'qty_cabang_terima' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $spk = Spk::findOrFail($id);

        $spk->update([
            'status_terima_barang' => $validated['status_terima_barang'],
            'qty_cabang_terima' => $validated['qty_cabang_terima'] ?? $spk->qty_cabang_terima,
            'tgl_terima_barang' => $validated['status_terima_barang'] == 1 ? now() : null,
            'keterangan' => $validated['keterangan'] ?? $spk->keterangan,
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);

        return back()->with('success', 'Status terima barang berhasil diperbarui.');
    }
}