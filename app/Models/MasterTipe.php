<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MasterTipe extends Model
{
    protected $table = 'master_tipe';
    protected $primaryKey = 'id_tipe';

    protected $fillable = [
        'nama',
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

    public function details()
    {
        return $this->hasMany(MasterProdukDetail::class, 'id_tipe', 'id_tipe');
    }
}
