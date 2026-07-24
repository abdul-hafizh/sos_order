<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MasterProdukGambar extends Model
{
    protected $table = 'master_produk_gambar';
    protected $primaryKey = 'id_produk_gambar';

    protected $fillable = [
        'id_produk',
        'nama_file',
        'path_file',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });
    }

    public function produk()
    {
        return $this->belongsTo(MasterProduk::class, 'id_produk', 'id_produk');
    }
}
