<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Keranjang;
use App\Models\KeranjangDetail;
use App\Models\Spk;
use Illuminate\Support\Facades\DB;
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

    public function updateNamaBarangBaru(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
        ]);

        $keranjang = $this->getDraftKeranjang();

        $detail = KeranjangDetail::where('id_keranjang_detail', $id)
            ->where('id_keranjang', $keranjang->id_keranjang)
            ->where('tipe_item', 'barang_baru')
            ->firstOrFail();

        $detail->update([
            'nama_barang' => $validated['nama_barang'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Nama permintaan barang baru berhasil diperbarui.');
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

    public function pesanSekarang()
    {
        $keranjang = $this->getDraftKeranjang();

        $items = KeranjangDetail::with('barang')
            ->where('id_keranjang', $keranjang->id_keranjang)
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Keranjang masih kosong.');
        }

        DB::transaction(function () use ($items, $keranjang) {
            $user = auth()->user();

            foreach ($items as $item) {
                Spk::create([
                    'period' => now()->format('Y-m-d'),
                    'po_ke' => 1,

                    // sesuaikan kalau field cabang user Anda beda
                    'kode_cabang' => $user->kode_cabang ?? null,

                    'kode_barang' => $item->barang?->kode_barang,
                    'nama_barang' => $item->nama_barang,
                    'qty_last' => 0,
                    'qty_cabang_terima' => 0,
                    'qty' => $item->qty,

                    'harga_beli' => $item->barang?->harga_beli ?? 0,
                    'harga_jual' => $item->barang?->harga_jual ?? 0,

                    'satuan' => $item->satuan,
                    'satuan_pos' => $item->satuan,
                    'qty_pos' => 1,

                    'kode_vendor' => null,
                    'kirim_langsung' => 0,
                    'status_terima_barang' => 0,
                    'tgl_terima_barang' => null,
                    'status_kirim_barang' => 0,
                    'tgl_kirim_barang' => null,
                    'active' => 1,
                    'status_validasi' => 0,
                    'tgl_validasi' => null,

                    'keterangan' => $item->tipe_item === 'barang_baru'
                        ? 'Permintaan barang baru'
                        : $item->catatan,

                    'modified_by' => auth()->id(),
                    'modified_date' => now(),
                ]);
            }

            $keranjang->details()->delete();

            $keranjang->update([
                'status' => 'selesai',
            ]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Pesanan berhasil disimpan ke SPK.');
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