<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LunchCustomPurchaseItem extends Model
{
    protected $table = 'lunch_custom_purchase_items';

    protected $fillable = [
        'lunch_custom_purchase_id',
        'item_name',
        'qty',
        'unit',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'qty'        => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(LunchCustomPurchase::class, 'lunch_custom_purchase_id');
    }

    protected static function booted()
    {
        static::saving(function (self $item) {
            $item->subtotal = (float) $item->qty * (float) $item->unit_price;
        });
    }
}
