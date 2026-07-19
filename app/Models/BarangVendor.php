<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangVendor extends Model
{
    protected $table = 't_barang_vendor';

    protected $primaryKey = 'id_barang_vendor';

    public $timestamps = false;

    protected $fillable = [
        'kode_barang',
        'kode_vendor',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function barang()
    {
        return $this->belongsTo(
            Barang::class,
            'kode_barang',
            'kode_barang'
        );
    }

    public function vendor()
    {
        return $this->belongsTo(
            Vendor::class,
            'kode_vendor',
            'kode_vendor'
        );
    }
}