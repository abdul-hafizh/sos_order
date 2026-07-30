<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MasterUom extends Model
{
    protected $table = 'master_uom';
    protected $primaryKey = 'id_uom';

    protected $fillable = [
        'nama_uom',
        'deskripsi',
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
}
