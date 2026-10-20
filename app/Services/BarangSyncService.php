<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\MasterProduk;
use App\Models\MasterProdukDetail;
use App\Models\MItem;
use App\Models\Ppn;
use App\Models\Spk;
use App\Models\SpkLog;
use Illuminate\Support\Str;

/**
 * Sinkronisasi data barang (t_barang + master_produk_detail) ke t_spk dan m_item.
 * Dipakai dari menu Barang maupun Master Produk Detail supaya keduanya konsisten.
 */
class BarangSyncService
{
    /**
     * Dipanggil setelah master_produk_detail diedit dari menu Master Produk Detail:
     * samakan satuan & kategori di t_barang, lalu teruskan ke t_spk dan m_item.
     */
    public function syncFromProdukDetail(MasterProdukDetail $produkDetail): void
    {
        if (!$produkDetail->kode_barang) {
            return;
        }

        $barang = Barang::where('kode_barang', $produkDetail->kode_barang)->first();

        if (!$barang) {
            return;
        }

        $produkDetail->load(['satuan', 'uom']);

        $barang->fill([
            'satuan' => $produkDetail->satuan?->nama ?? $barang->satuan,
            'category_code' => $produkDetail->category_id ?? $barang->category_code,
        ]);

        if ($barang->isDirty()) {
            $barang->modified_by = auth()->id();
            $barang->modified_date = now();
            $barang->save();
        }

        $this->syncSpk($barang, $produkDetail);
        $this->syncMItemIfEligible($barang, $produkDetail);
    }

    /**
     * Dipanggil setelah nama_produk diedit dari menu Master Produk: nama barang semua
     * barang yang memakai produk ini ikut diganti, lalu diteruskan ke t_spk dan m_item.
     */
    public function syncFromProduk(MasterProduk $produk): void
    {
        $details = MasterProdukDetail::where('id_produk', $produk->id_produk)
            ->whereNotNull('kode_barang')
            ->get();

        foreach ($details as $produkDetail) {
            $barang = Barang::where('kode_barang', $produkDetail->kode_barang)->first();

            if (!$barang) {
                continue;
            }

            // t_barang.nama_barang & m_item.itemname maksimal 100 karakter.
            $barang->nama_barang = Str::limit($produk->nama_produk, 100, '');

            if ($barang->isDirty()) {
                $barang->modified_by = auth()->id();
                $barang->modified_date = now();
                $barang->save();
            }

            $this->syncSpk($barang, $produkDetail);
            $this->syncMItemIfEligible($barang, $produkDetail);
        }
    }

    /**
     * Pindahkan SPK milik barang lama ke barang baru, dipakai saat kode_barang di
     * Master Produk Detail diganti (mis. barang baru dari SPK ternyata dobel dengan
     * barang yang sudah ada). Nama, satuan, dll. disinkron sesudahnya lewat syncSpk().
     */
    public function moveSpk(Barang $from, Barang $to): void
    {
        Spk::where(function ($query) use ($from) {
            $query->where('id_barang', $from->id_barang)
                ->orWhere(function ($q) use ($from) {
                    $q->whereNull('id_barang')->where('kode_barang', $from->kode_barang);
                });
        })->get()->each(function (Spk $spk) use ($to) {
            $spk->update([
                'id_barang' => $to->id_barang,
                'kode_barang' => $to->kode_barang,
                'modified_date' => now(),
            ]);

            $this->writeSpkLog($spk, 'u');
        });
    }

    /**
     * Update semua baris t_spk milik barang ini (dicocokkan lewat id_barang):
     * kode_barang, nama_barang, harga, satuan, satuan_pos, qty_pos dan kolom PPN.
     * id_ppn & ppn_persen diambil dari m_ppn yang sedang aktif - ppn_persen disimpan
     * apa adanya (mis. 11.00).
     *
     * Harga beli & jual SPK selalu disamakan dengan t_barang, supaya harga di Dashboard,
     * t_spk, t_spk_log dan m_item (yang dihitung dari harga_jual_include_ppn SPK) sama.
     *
     * Satuan mengikuti master: satuan = master_satuan (sama dengan t_barang.satuan),
     * satuan_pos = master_uom (sama dengan m_item.uom). modified_date hanya diperbarui
     * pada baris yang datanya benar-benar berubah, dan tiap perubahan dicatat sebagai
     * baris baru di t_spk_log. modified_by sengaja tidak diubah karena dipakai sebagai
     * ID pemesan untuk notifikasi Telegram di menu SPK.
     */
    public function syncSpk(Barang $barang, ?MasterProdukDetail $produkDetail = null): void
    {
        $produkDetail ??= MasterProdukDetail::where('kode_barang', $barang->kode_barang)->first();
        $produkDetail?->loadMissing(['satuan', 'uom']);

        $ppn = Ppn::where('active', 1)->orderByDesc('id_ppn')->first();

        $idPpn = $ppn?->id_ppn;
        $ppnPersen = $ppn ? (float) $ppn->persen_ppn : 0;

        $satuanMaster = $produkDetail?->satuan?->nama ?: $barang->satuan;
        $satuanPosMaster = $produkDetail?->uom?->nama_uom ?: ($barang->satuan_pos ?: $satuanMaster);

        Spk::where('id_barang', $barang->id_barang)->get()->each(function (Spk $spk) use ($idPpn, $ppnPersen, $barang, $satuanMaster, $satuanPosMaster) {
            // Kolom harga t_barang & t_spk sama-sama varchar - teks harga disalin apa
            // adanya supaya isinya persis sama, angka float hanya untuk hitung PPN.
            $hargaJualDpp = (float) $barang->harga_jual;
            $hargaJual = $barang->harga_jual ?? '0';
            $hargaBeli = $barang->harga_beli ?? '0';
            $nilaiPpn = ($ppnPersen / 100) * $hargaJualDpp;

            $satuan = $satuanMaster ? Str::limit((string) $satuanMaster, 10, '') : $spk->satuan;
            $satuanPos = $satuanPosMaster ? Str::limit((string) $satuanPosMaster, 10, '') : $spk->satuan_pos;
            $qtyPos = (float) $barang->qty_pos > 0 ? $barang->qty_pos : $spk->qty_pos;

            $changes = $this->changedAttributes($spk, [
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => Str::limit($barang->nama_barang, 50, ''),
                'harga_beli' => $hargaBeli,
                'satuan' => $satuan ?: null,
                'satuan_pos' => $satuanPos ?: null,
                'qty_pos' => $qtyPos,
                'harga_jual' => $hargaJual,
                'id_ppn' => $idPpn,
                'ppn_persen' => $ppnPersen,
                'harga_jual_dpp' => $hargaJualDpp,
                'nilai_ppn' => $nilaiPpn,
                'harga_jual_include_ppn' => $hargaJualDpp + $nilaiPpn,
            ]);

            if (!empty($changes)) {
                $spk->update($changes + ['modified_date' => now()]);
                $this->writeSpkLog($spk, 'u');
            }
        });
    }

    /**
     * Catat snapshot SPK ke t_spk_log sebagai baris baru, mengikuti pola log lama:
     * a = jenis aksi (i = insert, u = update, d = delete), b = waktu dicatat.
     */
    public function writeSpkLog(Spk $spk, string $aksi): void
    {
        SpkLog::create($spk->only([
            'id_po',
            'period',
            'po_ke',
            'kode_cabang',
            'kode_barang',
            'nama_barang',
            'qty_last',
            'qty_cabang_terima',
            'qty',
            'harga_beli',
            'harga_jual',
            'satuan',
            'satuan_pos',
            'qty_pos',
            'kode_vendor',
            'kirim_langsung',
            'status_terima_barang',
            'tgl_terima_barang',
            'status_kirim_barang',
            'tgl_kirim_barang',
            'active',
            'status_validasi',
            'tgl_validasi',
            'keterangan',
            'modified_by',
            'modified_date',
        ]) + [
            'a' => $aksi,
            'b' => now(),
        ]);
    }

    /**
     * Ambil hanya kolom yang nilainya benar-benar berubah. Angka dibandingkan secara
     * numerik karena SQL Server mengembalikan decimal sebagai string (mis. "12000.00"),
     * sementara isDirty() Eloquent membandingkannya sebagai teks sehingga selalu
     * dianggap berubah.
     */
    private function changedAttributes(Spk $spk, array $values): array
    {
        return array_filter($values, function ($value, $key) use ($spk) {
            $original = $spk->getAttribute($key);

            if (is_numeric($value) && is_numeric($original)) {
                return abs((float) $value - (float) $original) > 0.0001;
            }

            return (string) ($value ?? '') !== (string) ($original ?? '');
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Kolom master_produk_detail yang wajib terisi sebelum boleh disinkron ke m_item.
     */
    public function missingProdukDetailFieldsForSync(MasterProdukDetail $produkDetail): array
    {
        $required = [
            'kode_barang' => $produkDetail->kode_barang,
            'category_id' => $produkDetail->category_id,
            'id_tipe' => $produkDetail->id_tipe,
            'id_satuan' => $produkDetail->id_satuan,
            'id_uom' => $produkDetail->id_uom,
        ];

        return array_keys(array_filter($required, fn($value) => empty($value)));
    }

    /**
     * Kolom harga t_barang yang wajib terisi (bukan kosong/0) sebelum boleh disinkron ke m_item.
     */
    public function missingHargaFieldsForSync(Barang $barang): array
    {
        $required = [
            'harga_beli_before' => $barang->harga_beli_before,
            'harga_beli' => $barang->harga_beli,
            'harga_jual_before' => $barang->harga_jual_before,
            'harga_jual' => $barang->harga_jual,
            'harga_jual_jumbo' => $barang->harga_jual_jumbo,
            'harga_jual_jumbo_before' => $barang->harga_jual_jumbo_before,
        ];

        return array_keys(array_filter($required, fn($value) => $value === null || (float) $value <= 0));
    }

    /**
     * Sinkron otomatis ke m_item begitu semua kolom wajib (produk detail + harga) sudah
     * terisi. Pakai updateOrCreate berdasarkan itemcode - insert kalau baris m_item-nya
     * belum ada, atau resinkron ulang semua kolomnya tiap kali barang diupdate kalau
     * sudah ada, supaya buyingprice/sellingprice ikut naik-turun sesuai t_spk & margin.
     */
    public function syncMItemIfEligible(Barang $barang, MasterProdukDetail $produkDetail): void
    {
        $missingDetail = $this->missingProdukDetailFieldsForSync($produkDetail);
        $missingHarga = $this->missingHargaFieldsForSync($barang);

        if (!empty($missingDetail) || !empty($missingHarga)) {
            return;
        }

        $produkDetail->loadMissing(['category', 'uom']);

        $spkRows = Spk::where('id_barang', $barang->id_barang)->get();
        $totalSpkQty = $spkRows->sum(fn($spk) => (float) $spk->qty);
        $endstock = $totalSpkQty * (float) $barang->qty_pos;

        $latestSpk = $spkRows->sortByDesc('id_po')->first();
        $buyingPrice = (float) ($latestSpk->harga_jual_include_ppn ?? 0);

        $marginPercent = (float) ($produkDetail->category->margin ?? 0);
        $sellingPrice = round($buyingPrice * (1 + $marginPercent / 100), 2);

        MItem::updateOrCreate(['itemcode' => $barang->kode_barang], [
            'itemcodeint' => $barang->kode_barang,
            'itemcodeint1' => $barang->kode_barang,
            'barcode1' => $barang->kode_barang,
            'barcode2' => $barang->kode_barang,
            'itemname' => $barang->nama_barang,
            'itemname1' => $barang->nama_barang,
            'categorycode' => $produkDetail->category_id,
            'uom' => $produkDetail->uom->nama_uom ?? null,
            'minstock' => $barang->min_stok,
            'maxstock' => $barang->max_stok,
            'endstock' => $endstock,
            'buyingprice' => $buyingPrice,
            'lastbuyingprice' => $barang->harga_beli_before,
            'sellingprice' => $sellingPrice,
            'sellingpricealt' => $sellingPrice,
            'nonaktif' => $barang->active ? 0 : 1,
            'canbesold' => $barang->active ? 1 : 0,
        ]);
    }
}
