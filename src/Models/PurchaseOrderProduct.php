<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use SmartTill\Core\Casts\PriceCast;

class PurchaseOrderProduct extends Pivot
{
    protected $table = 'purchase_order_variation';

    public $incrementing = true;

    protected $fillable = [
        'purchase_order_id',
        'variation_id',
        'description',
        'requested_quantity',
        'requested_unit_id',
        'requested_unit_price',
        'requested_tax_percentage',
        'requested_tax_amount',
        'requested_supplier_percentage',
        'requested_supplier_is_percentage',
        'requested_supplier_price',
        'received_quantity',
        'received_unit_id',
        'received_unit_price',
        'received_tax_percentage',
        'received_tax_amount',
        'received_supplier_percentage',
        'received_supplier_is_percentage',
        'received_supplier_price',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'float',
            'requested_unit_price' => PriceCast::class,
            'requested_tax_amount' => PriceCast::class,
            'requested_supplier_price' => PriceCast::class,
            'received_quantity' => 'float',
            'received_unit_price' => PriceCast::class,
            'received_tax_amount' => PriceCast::class,
            'received_supplier_price' => PriceCast::class,
            'requested_tax_percentage' => 'decimal:6',
            'requested_supplier_percentage' => 'decimal:6',
            'requested_supplier_is_percentage' => 'boolean',
            'received_tax_percentage' => 'decimal:6',
            'received_supplier_percentage' => 'decimal:6',
            'received_supplier_is_percentage' => 'boolean',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function requestedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'requested_unit_id');
    }

    public function receivedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'received_unit_id');
    }

    /**
     * Get the product through the variation relationship
     */
    public function product(): BelongsTo
    {
        return $this->variation->product();
    }
}
