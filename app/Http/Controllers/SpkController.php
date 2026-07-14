<?php

namespace App\Http\Controllers;

use App\Models\Spk;
use App\Models\Barang;
use App\Models\BarangDetail;
use App\Models\BarangGambar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Libraries\SendTelegram;
use Inertia\Inertia;

class SpkController extends Controller
{
    public function index(Request $request)
    {
        $spks = Spk::query()
            ->with(['barang.details.gambars', 'cabang'])
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

    public function updateKetersediaan(Request $request, $id)
    {
        $validated = $request->validate([
            'is_available' => 'required|integer|in:0,1,2',
        ]);

        $spk = Spk::findOrFail($id);

        $spk->update([
            'is_available' => $validated['is_available'],
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);        

        try {
            $statusLabels = [
                0 => 'MENUNGGU VERIFIKASI',
                1 => 'TERSEDIA (Disetujui)',
                2 => 'TIDAK TERSEDIA (Ditolak)',
            ];
            $statusText = $statusLabels[$validated['is_available']];

            $hargaBeliFormat = 'Rp ' . number_format($spk->harga_beli, 0, ',', '.');
            $tanggalPesanan = $spk->modified_date ? date('d-m-Y H:i', strtotime($spk->modified_date)) : now()->format('d-m-Y H:i');

            $message = "<b>PEMBERITAHUAN STATUS PESANAN BARANG</b>\n\n";
            $message .= "Yth. Rekan Cabang <b>{$spk->kode_cabang}</b>,\n";
            $message .= "Berikut adalah pembaruan status ketersediaan untuk barang yang Anda ajukan:\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "<b>Nama Barang :</b> {$spk->nama_barang}\n";
            $message .= "<b>Qty Pesanan :</b> {$spk->qty} {$spk->satuan}\n";
            $message .= "<b>Harga Beli   :</b> {$hargaBeliFormat}\n";
            $message .= "<b>Tgl Pesanan  :</b> {$tanggalPesanan}\n";
            $message .= "<b>Cabang       :</b> {$spk->kode_cabang}\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "<b>STATUS SAAT INI :</b>\n";
            $message .= "<b>[ {$statusText} ]</b>\n\n";
            
            if ($validated['is_available'] == 0) {
                $message .= "<i>Catatan: Permintaan Anda sedang dalam proses verifikasi oleh tim pusat. Harap menunggu informasi selanjutnya.</i>";
            } elseif ($validated['is_available'] == 1) {
                $message .= "<i>Catatan: Barang telah dinyatakan tersedia dan akan diproses untuk langkah pengadaan/pengiriman selanjutnya.</i>";
            } else {
                $message .= "<i>Catatan: Mohon maaf, barang tidak dapat disediakan saat ini. Silakan hubungi admin pusat untuk informasi lebih lanjut.</i>";
            }

            $userCabang = User::where('id', $spk->modified_by)
                ->whereNotNull('telegram_chat_id')
                ->where('telegram_chat_id', '!=', '')
                ->first();

            if ($userCabang && $userCabang->telegram_chat_id) {
                SendTelegram::sendMessage($userCabang->telegram_chat_id, $message);
            } else {
                Log::warning("Gagal mengirim notif Telegram SPK ID #{$spk->id_po}: User dengan ID Pembuat '{$spk->modified_by}' tidak ditemukan atau belum mengisi telegram_chat_id.");
            }

        } catch (\Exception $e) {
            Log::error("Telegram Notification Error: " . $e->getMessage());
        }

        return back()->with('success', 'Status ketersediaan barang berhasil diperbarui.');
    }

    public function show($id)
    {
        $spk = Spk::with(['barang.details.gambars', 'cabang'])->findOrFail($id);

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