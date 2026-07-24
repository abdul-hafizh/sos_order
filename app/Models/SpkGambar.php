<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpkGambar extends Model
{
    protected $table = 'spk_gambar';
    protected $primaryKey = 'id_spk_gambar';

    protected $fillable = [
        'id_po',
        'gambar',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class, 'id_po', 'id_po');
    }
}
