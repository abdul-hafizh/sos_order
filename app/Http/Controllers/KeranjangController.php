<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Keranjang;
use App\Models\KeranjangDetail;
use App\Models\Spk;
use App\Models\SpkGambar;
use App\Models\User;
use App\Libraries\SendTelegram;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    private function addBarangToKeranjang(Keranjang $keranjang, Barang $barang, int $qty): void
    {
        $detail = KeranjangDetail::where('id_keranjang', $keranjang->id_keranjang)
            ->where('id_barang', $barang->id_barang)
            ->where('tipe_item', 'barang_tersedia')
            ->first();

        if ($detail) {
            $detail->increment('qty', $qty);
            return;
        }

        KeranjangDetail::create([
            'id_keranjang' => $keranjang->id_keranjang,
            'id_barang' => $barang->id_barang,
            'nama_barang' => $barang->nama_barang,
            'qty' => $qty,
            'satuan' => $barang->satuan,
            'tipe_item' => 'barang_tersedia',
        ]);
    }

    public function storeBarang(Request $request)
    {
        $validated = $request->validate([
            'id_barang' => 'required|integer|exists:t_barang,id_barang',
            'qty' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $keranjang = $this->getDraftKeranjang();

        $this->addBarangToKeranjang(
            $keranjang,
            Barang::findOrFail($validated['id_barang']),
            $validated['qty']
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Barang berhasil dimasukkan ke keranjang.');
    }

    public function storeBarangBanyak(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|integer|exists:t_barang,id_barang',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $keranjang = $this->getDraftKeranjang();

        DB::transaction(function () use ($validated, $keranjang) {
            foreach ($validated['items'] as $row) {
                $this->addBarangToKeranjang(
                    $keranjang,
                    Barang::findOrFail($row['id_barang']),
                    $row['qty']
                );
            }
        });

        return back()->with('success', 'Varian berhasil dimasukkan ke keranjang.');
    }

    public function storeBarangBaru(Request $request)
    {
        $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:1'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
            'gambar' => ['nullable', 'image', 'max:10240'],
            'image_path' => ['nullable', 'string'],
        ]);

        // Tentukan path gambar (dari input file atau session hasil searchByImage)
        $gambarPath = $request->image_path ?: session('last_image_path');

        if (!$gambarPath && $request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('permintaan-barang', 'public');
        }

        $keranjang = Keranjang::firstOrCreate([
            'user_id' => auth()->id(),
            'status' => 'draft',
        ]);

        // 1. Simpan detail TANPA kolom gambar_permintaan
        $detail = $keranjang->items()->create([
            'id_barang' => null,
            'nama_barang' => $request->nama_barang,
            'qty' => $request->qty,
            'satuan' => $request->satuan,
            'catatan' => $request->catatan,
            'tipe_item' => 'barang_baru',
        ]);

        // 2. Simpan path gambar ke tabel relasi KeranjangDetailGambar
        if ($gambarPath) {
            \App\Models\KeranjangDetailGambar::create([
                'id_keranjang_detail' => $detail->id_keranjang_detail,
                'gambar' => $gambarPath,
            ]);
        }

        return back()->with('success', 'Permintaan barang baru berhasil dimasukkan ke keranjang.');
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

        $items = KeranjangDetail::with(['barang', 'gambar'])
            ->where('id_keranjang', $keranjang->id_keranjang)
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Keranjang masih kosong.');
        }

        $barangBaruItems = collect();

        DB::transaction(function () use ($items, $keranjang, &$barangBaruItems) {
            $user = auth()->user();

            foreach ($items as $item) {
                // Barang baru sengaja TIDAK di-provision ke t_barang di sini.
                // kode_barang baru digenerate saat admin klik "Tersedia" di menu SPK.
                $spk = Spk::create([
                    'period' => now()->format('Y-m-d'),
                    'po_ke' => 1,

                    'kode_cabang' => $user->kode_cabang ?? null,

                    'kode_barang' => $item->barang?->kode_barang,
                    'nama_barang' => Str::limit($item->nama_barang, 50, ''),
                    'id_barang' => $item->id_barang,
                    'qty_last' => 0,
                    'qty_cabang_terima' => 0,
                    'qty' => $item->qty,

                    'harga_beli' => $item->barang?->harga_beli ?? 0,
                    'harga_jual' => $item->barang?->harga_jual ?? 0,

                    'satuan' => Str::limit($item->satuan, 10, ''),
                    'satuan_pos' => Str::limit($item->satuan, 10, ''),
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

                    'gambar_permintaan' => $item->gambar->first()?->gambar,

                    'modified_by' => auth()->id(),
                    'modified_date' => now(),
                ]);

                foreach ($item->gambar as $gambar) {
                    SpkGambar::create([
                        'id_po' => $spk->id_po,
                        'gambar' => $gambar->gambar,
                    ]);
                }

                if ($item->tipe_item === 'barang_baru') {
                    $barangBaruItems->push([
                        'nama_barang' => $item->nama_barang,
                        'qty' => $item->qty,
                        'satuan' => $item->satuan,
                    ]);
                }
            }

            $keranjang->details()->delete();

            $keranjang->update([
                'status' => 'selesai',
            ]);
        });

        if ($barangBaruItems->isNotEmpty()) {
            $this->notifyAdminBarangBaru($barangBaruItems, auth()->user());
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Pesanan berhasil disimpan ke SPK.');
    }

    private function notifyAdminBarangBaru($items, $requester)
    {
        $admins = User::where('kode_cabang', 'GSOS')
            ->where('is_admin', 1)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $daftarBarang = $items->map(function ($item, $index) {
            $no = $index + 1;
            $satuan = $item['satuan'] ?: '';

            return "{$no}. {$item['nama_barang']} — {$item['qty']} {$satuan}";
        })->implode("\n");

        $message = "<b>PERMINTAAN BARANG BARU</b>\n\n";
        $message .= "Cabang <b>{$requester->kode_cabang}</b> ({$requester->nama_user}) baru saja mengajukan permintaan barang baru:\n\n";
        $message .= "{$daftarBarang}\n\n";
        $message .= "Tanggal: <b>" . now()->format('d-m-Y H:i') . "</b>\n\n";
        $message .= "<i>Mohon segera ditindaklanjuti di menu SPK.</i>";

        foreach ($admins as $admin) {
            try {
                SendTelegram::sendMessage($admin->telegram_chat_id, $message);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim notifikasi Telegram barang baru ke admin '{$admin->user}': " . $e->getMessage());
            }
        }
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

    public function uploadGambar(Request $request, $id)
    {
        $request->validate([
            'gambar' => 'required|array',
            'gambar.*' => 'image|max:2048',
        ]);

        $detail = KeranjangDetail::findOrFail($id);

        foreach ($request->file('gambar') as $file) {
            $path = $file->store('permintaan-barang', 'public');

            \App\Models\KeranjangDetailGambar::create([
                'id_keranjang_detail' => $detail->id_keranjang_detail,
                'gambar' => $path,
            ]);
        }

        $keranjangId = $detail->id_keranjang;

        $keranjang = Keranjang::with(['items.gambar'])->find($keranjangId);

        return redirect()->back()->with('success', 'Gambar berhasil diunggah.', $keranjang);
    }
    public function gambar()
    {
        return $this->hasMany(KeranjangDetailGambar::class, 'id_keranjang_detail', 'id_keranjang_detail');
    }
}