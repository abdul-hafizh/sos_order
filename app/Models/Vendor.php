<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Vendor extends Model
{
    protected $table = 't_vendor';
    protected $primaryKey = 'id_vendor';
    public $timestamps = false;

    protected $fillable = [
        'kode_vendor',
        'nama_vendor',
        'alamat',
        'telp',
        'pic',
        'kota',
        'modified_by',
        'modified_date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->modified_by = Auth::id() ?? 1;
            $model->modified_date = now();
        });
    }

    public function barangs()
    {
        return $this->hasMany(
            BarangVendor::class,
            'kode_vendor',
            'kode_vendor'
        );
    }
}