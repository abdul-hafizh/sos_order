<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MasterProdukDetailGambar extends Model
{
    protected $table = 'master_produk_detail_gambar';
    protected $primaryKey = 'id_produk_gambar';

    protected $fillable = [
        'id_produk_detail',
        'nama_file',
        'path_file',
        'created_by',
        'description',
        'embedding',
        'embedding_model',
        'embedding_generated_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'embedding_generated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });
    }

    public function produkDetail()
    {
        return $this->belongsTo(MasterProdukDetail::class, 'id_produk_detail', 'id_produk_detail');
    }
}
