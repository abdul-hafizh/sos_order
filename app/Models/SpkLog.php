<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpkLog extends Model
{
    protected $table = 't_spk_log';
    protected $primaryKey = 'id_po';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
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
    ];
}
