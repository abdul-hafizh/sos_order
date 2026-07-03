<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    protected $table = 't_divisi';
    protected $primaryKey = 'id_divisi';
    public $timestamps = false;
    protected $fillable = ['divisi_name'];
}