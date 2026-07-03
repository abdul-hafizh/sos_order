<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 't_cabang';

    protected $primaryKey = 'id_cabang';


    public $timestamps = false;

    protected $fillable = [
        'kode_cabang',
        'cabang_nama',
        'alamat',
        'kodepos',
        'telp',
        'fax',
        'email',
        'kota',
        'pic',
        'sos',
        'addstock',
        'modified_by',
        'modified_date',
        'IsCabang'
    ];
}