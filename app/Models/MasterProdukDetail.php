<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MasterProdukDetail extends Model
{
    protected $table = 'master_produk_detail';
    protected $primaryKey = 'id_produk_detail';

    protected $fillable = [
        'id_produk',
        'kode_barang',
        'id_tipe',
        'id_satuan',
        'id_berat',
        'id_ukuran',
        'id_warna',
        'id_karakter',
        'id_uom',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });

        static::saving(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    public function produk()
    {
        return $this->belongsTo(MasterProduk::class, 'id_produk', 'id_produk');
    }

    public function tipe()
    {
        return $this->belongsTo(MasterTipe::class, 'id_tipe', 'id_tipe');
    }

    public function satuan()
    {
        return $this->belongsTo(MasterSatuan::class, 'id_satuan', 'id_satuan');
    }

    public function berat()
    {
        return $this->belongsTo(MasterBerat::class, 'id_berat', 'id_berat');
    }

    public function ukuran()
    {
        return $this->belongsTo(MasterUkuran::class, 'id_ukuran', 'id_ukuran');
    }

    public function warna()
    {
        return $this->belongsTo(MasterWarna::class, 'id_warna', 'id_warna');
    }

    public function karakter()
    {
        return $this->belongsTo(MasterKarakter::class, 'id_karakter', 'id_karakter');
    }

    public function uom()
    {
        return $this->belongsTo(MasterUom::class, 'id_uom', 'id_uom');
    }

    public function gambars()
    {
        return $this->hasMany(MasterProdukDetailGambar::class, 'id_produk_detail', 'id_produk_detail');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }
}
