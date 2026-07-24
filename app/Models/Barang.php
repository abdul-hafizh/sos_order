<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 't_barang';

    protected $primaryKey = 'id_barang';

    public $timestamps = false;

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'harga_beli_before',
        'harga_beli',
        'harga_jual_before',
        'harga_jual',
        'harga_jual_jumbo',
        'harga_jual_jumbo_before',
        'margin',
        'satuan',
        'satuan_pos',
        'qty_pos',
        'kirim_langsung',
        'min_vendor',
        'min_cabang',
        'stok',
        'min_stok',
        'max_stok',
        'active',
        'category_code',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'id_barang' => 'integer',
        'kirim_langsung' => 'boolean',
        'stok' => 'integer',
        'min_stok' => 'integer',
        'max_stok' => 'integer',
        'active' => 'boolean',
        'modified_by' => 'integer',
        'modified_date' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(BarangDetail::class, 'id_barang', 'id_barang');
    }

    public function vendors()
    {
        return $this->hasMany(
            BarangVendor::class,
            'kode_barang',
            'kode_barang'
        );
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'categorycode');
    }

    public function produk()
    {
        return $this->hasOne(MasterProduk::class, 'kode_barang', 'kode_barang');
    }
}