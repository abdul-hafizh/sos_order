<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MItem extends Model
{
    protected $table = 'm_item';

    protected $primaryKey = 'itemcode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'itemcode',
        'itemcodeint',
        'itemcodeint1',
        'barcode1',
        'barcode2',
        'itemname',
        'itemname1',
        'buyingprice',
        'sellingprice',
        'minstock',
        'maxstock',
        'endstock',
        'nonaktif',
        'canbesold',
    ];
}
