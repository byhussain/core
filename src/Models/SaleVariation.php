<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use SmartTill\Core\Casts\PriceCast;

class SaleVariation extends Pivot
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => PriceCast::class,
            'tax' => PriceCast::class,
            'discount' => PriceCast::class,
            'discount_percentage' => 'decimal:6',
            'total' => PriceCast::class,
            'supplier_price' => PriceCast::class,
            'supplier_total' => PriceCast::class,
            'is_preparable' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
