<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Keranjang;
use App\Models\KeranjangDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KeranjangController extends Controller
{
    private function getDraftKeranjang(): Keranjang
    {
        return Keranjang::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'status' => 'draft',
            ],
            [
                'user_id' => Auth::id(),
                'status' => 'draft',
            ]
        );
    }

    public function storeBarang(Request $request)
    {
        $validated = $request->validate([
            'id_barang' => 'required|integer|exists:t_barang,id_barang',
            'qty' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $barang = Barang::findOrFail($validated['id_barang']);

        $keranjang = $this->getDraftKeranjang();

        $detail = KeranjangDetail::where('id_keranjang', $keranjang->id_keranjang)
            ->where('id_barang', $barang->id_barang)
            ->where('tipe_item', 'barang_tersedia')
            ->first();

        if ($detail) {
            $detail->increment('qty', $validated['qty']);
        } else {
            KeranjangDetail::create([
                'id_keranjang' => $keranjang->id_keranjang,
                'id_barang' => $barang->id_barang,
                'nama_barang' => $barang->nama_barang,
                'qty' => $validated['qty'],
                'satuan' => $barang->satuan,
                'tipe_item' => 'barang_tersedia',
                'gambar_permintaan' => null,
                'catatan' => $validated['catatan'] ?? null,
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Barang berhasil dimasukkan ke keranjang.');
    }

    public function storeBarangBaru(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'satuan' => 'nullable|string|max:50',
            'catatan' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_path' => 'nullable|string',
        ]);

        $keranjang = $this->getDraftKeranjang();

        $gambarPath = $request->input('image_path');

        if (!$gambarPath && $request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('permintaan-barang', 'public');
        }

        KeranjangDetail::create([
            'id_keranjang' => $keranjang->id_keranjang,
            'id_barang' => null,
            'nama_barang' => $validated['nama_barang'],
            'qty' => $validated['qty'],
            'satuan' => $validated['satuan'] ?? null,
            'tipe_item' => 'barang_baru',
            'gambar_permintaan' => $gambarPath,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Permintaan barang baru berhasil dimasukkan ke keranjang.');
    }

    public function updateQty(Request $request, $id)
    {
        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $keranjang = $this->getDraftKeranjang();

        $detail = KeranjangDetail::where('id_keranjang_detail', $id)
            ->where('id_keranjang', $keranjang->id_keranjang)
            ->firstOrFail();

        $detail->update([
            'qty' => $validated['qty'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Qty keranjang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $keranjang = $this->getDraftKeranjang();

        $detail = KeranjangDetail::where('id_keranjang_detail', $id)
            ->where('id_keranjang', $keranjang->id_keranjang)
            ->firstOrFail();

        $detail->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Item berhasil dihapus dari keranjang.');
    }
}