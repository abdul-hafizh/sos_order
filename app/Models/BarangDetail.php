<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangDetail extends Model
{
    protected $table = 't_barang_detail';

    protected $primaryKey = 'id_barang_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'nama_variant',
        'kode_variant',
        'harga_beli',
        'harga_jual',
        'harga_jual_jumbo',
        'stok',
        'active',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'id_barang_detail' => 'integer',
        'id_barang' => 'integer',
        'stok' => 'integer',
        'active' => 'boolean',
        'modified_by' => 'integer',
        'modified_date' => 'datetime',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function gambars()
    {
        return $this->hasMany(BarangGambar::class, 'id_barang_detail', 'id_barang_detail');
    }
}