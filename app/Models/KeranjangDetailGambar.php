<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeranjangDetailGambar extends Model
{
    protected $table = 'keranjang_detail_gambar';

    protected $primaryKey = 'id_gambar';

    protected $fillable = [
        'id_keranjang_detail',
        'gambar',
    ];

    protected $casts = [
        'id_keranjang_detail' => 'integer',
    ];

    public function keranjangDetail()
    {
        return $this->belongsTo(
            KeranjangDetail::class,
            'id_keranjang_detail',
            'id_keranjang_detail'
        );
    }
}