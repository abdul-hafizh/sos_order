<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'ref_menu';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'parent_menu',
        'link_menu',
        'nama_menu',
        'menu_order'
    ];
}