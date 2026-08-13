<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'm_category_2026';

    protected $primaryKey = 'categorycode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'categorycode',
        'categoryname',
        'gambar',
        'komisi',
        'komisinilai',
        'supervisor',
        'remarks',
        'cekpengambilan',
        'warehouseprinter',
        'markup',
        'margin',
        'user_id',
    ];

    protected $casts = [
        'komisi' => 'decimal:2',
        'komisinilai' => 'decimal:2',
        'cekpengambilan' => 'boolean',
    ];

    protected $appends = [
        'code',
        'name',
        'gambar_url',
    ];

    public function getCodeAttribute()
    {
        return $this->categorycode;
    }

    public function getNameAttribute()
    {
        return $this->categoryname;
    }

    public function getGambarUrlAttribute()
    {
        return $this->gambar ? asset('storage/' . $this->gambar) : null;
    }
}