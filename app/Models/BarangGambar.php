<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangGambar extends Model
{
    protected $table = 't_barang_gambar';

    protected $primaryKey = 'id_barang_gambar';

    public $timestamps = false;

    protected $fillable = [
        'id_barang_detail',
        'nama_file',
        'path_file',
        'active',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'id_barang_gambar' => 'integer',
        'id_barang_detail' => 'integer',
        'active' => 'boolean',
        'modified_by' => 'integer',
        'modified_date' => 'datetime',
    ];

    public function barangDetail()
    {
        return $this->belongsTo(BarangDetail::class, 'id_barang_detail', 'id_barang_detail');
    }
}