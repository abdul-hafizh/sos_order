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
        'barcode3',
        'itemname',
        'itemname1',
        'categorycode',
        'suppliercode',
        'uom',
        'transaction',
        'nonaktif',
        'plu',
        'itemtype',
        'minstock',
        'maxstock',
        'endstock',
        'totalbuyingqty',
        'buyingprice',
        'lastbuyingprice',
        'sellingprice',
        'sellingpricealt',
        'sellingprice0',
        'sellingprice1',
        'maxdiscount',
        'haveserial',
        'canbesold',
        'nonstock',
        'blockedpo',
        'blockedpodate',
        'reordertype',
        'typology',
        'warranty',
        'lastselldate',
        'lastpurchasedate',
        'lastpodate',
        'point',
        'location1',
        'location2',
        'getpoint',
        'discountmember',
        'itempkp',
        'pb1',
        'orderqty',
        'printstruk',
        'itemtrxtype',
        'itemcommission',
        'user_id',
        'divisi_code',
        'klik_meter',
        'subcategorycode',
    ];

    protected $casts = [
        'minstock' => 'decimal:2',
        'maxstock' => 'decimal:2',
        'endstock' => 'float',
        'totalbuyingqty' => 'float',
        'buyingprice' => 'decimal:2',
        'lastbuyingprice' => 'decimal:2',
        'sellingprice' => 'decimal:2',
        'sellingpricealt' => 'decimal:2',
        'sellingprice0' => 'decimal:2',
        'sellingprice1' => 'decimal:2',
        'maxdiscount' => 'decimal:2',
        'orderqty' => 'float',
        'itemcommission' => 'float',
        'blockedpodate' => 'date',
        'lastselldate' => 'date',
        'lastpurchasedate' => 'date',
        'lastpodate' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categorycode', 'categorycode');
    }
}
