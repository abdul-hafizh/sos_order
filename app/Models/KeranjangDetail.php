<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeranjangDetail extends Model
{
    protected $table = 'keranjang_detail';

    protected $primaryKey = 'id_keranjang_detail';

    protected $fillable = [
        'id_keranjang',
        'id_barang',
        'nama_barang',
        'qty',
        'satuan',
        'tipe_item',
        'catatan',
    ];

    
    protected $casts = [
        'id_keranjang' => 'integer',
        'id_barang' => 'integer',
        'qty' => 'integer',
    ];

    public function keranjang()
    {
        return $this->belongsTo(
            Keranjang::class,
            'id_keranjang',
            'id_keranjang'
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            Barang::class,
            'id_barang',
            'id_barang'
        );
    }

    public function gambar()
    {
        return $this->hasMany(
            KeranjangDetailGambar::class,
            'id_keranjang_detail',
            'id_keranjang_detail'
        );
    }
}