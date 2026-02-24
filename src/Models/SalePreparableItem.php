<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SmartTill\Core\Casts\PriceCast;

class SalePreparableItem extends Model
{
    protected $table = 'sale_preparable_items';

    protected $fillable = [
        'sale_id',
        'sequence',
        'preparable_variation_id',
        'variation_id',
        'stock_id',
        'quantity',
        'unit_price',
        'tax',
        'discount',
        'discount_type',
        'discount_percentage',
        'total',
        'supplier_price',
        'supplier_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => PriceCast::class,
            'tax' => PriceCast::class,
            'discount' => PriceCast::class,
            'discount_type' => 'string',
            'discount_percentage' => 'decimal:6',
            'total' => PriceCast::class,
            'supplier_price' => PriceCast::class,
            'supplier_total' => PriceCast::class,
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function preparableVariation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'preparable_variation_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
