<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Observers\StockObserver;

#[ObservedBy([StockObserver::class])]
class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_id',
        'barcode',
        'batch_number',
        'price',
        'sale_price',
        'sale_percentage',
        'tax_percentage',
        'tax_amount',
        'supplier_percentage',
        'supplier_price',
        'stock',
        'unit_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => PriceCast::class,
            'sale_price' => PriceCast::class,
            'tax_amount' => PriceCast::class,
            'supplier_price' => PriceCast::class,
            'sale_percentage' => 'decimal:6',
            'supplier_percentage' => 'decimal:6',
            'tax_percentage' => 'decimal:6',
            'stock' => 'decimal:6',
        ];
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
