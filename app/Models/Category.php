<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'm_category';

    protected $primaryKey = 'categorycode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'categorycode',
        'categoryname',
        'komisi',
        'komisinilai',
        'supervisor',
        'remarks',
        'cekpengambilan',
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
    ];

    public function getCodeAttribute()
    {
        return $this->categorycode;
    }

    public function getNameAttribute()
    {
        return $this->categoryname;
    }
}