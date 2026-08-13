<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ppn extends Model
{
    protected $table = 'm_ppn';

    protected $primaryKey = 'id_ppn';

    public $timestamps = false;

    protected $fillable = [
        'kode_ppn',
        'nama_ppn',
        'persen_ppn',
        'effective_from',
        'effective_to',
        'active',
        'keterangan',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'persen_ppn' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
        'modified_by' => 'integer',
        'modified_date' => 'datetime',
    ];
}
