<?php

namespace App\Models;

use App\Models\Barang;
use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    protected $table = 't_spk';
    protected $primaryKey = 'id_po';
    public $timestamps = false;

    protected $fillable = [
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
        'id_ppn',
        'ppn_persen',
        'harga_jual_dpp',
        'nilai_ppn',
        'harga_jual_include_ppn',
        'gambar_permintaan',
        'id_barang',
        'is_available',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }

    public function gambars()
    {
        return $this->hasMany(SpkGambar::class, 'id_po', 'id_po');
    }
}