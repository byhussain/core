<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Enums\SalePaymentMethod;
use SmartTill\Core\Enums\SalePaymentStatus;
use SmartTill\Core\Enums\SaleStatus;
use SmartTill\Core\Observers\SaleObserver;
use SmartTill\Core\Traits\TracksUserActivity;

#[ObservedBy([SaleObserver::class])]
class Sale extends Model
{
    use HasFactory, TracksUserActivity;

    protected $fillable = [
        'local_id',
        'store_id',
        'customer_id',
        'reference',
        'subtotal',
        'tax',
        'discount',
        'discount_type',
        'discount_percentage',
        'freight_fare',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'use_fbr',
        'paid_at',
        'note',
        'fbr_invoice_number',
        'fbr_qr_code',
        'fbr_synced_at',
        'fbr_response',
        'fbr_refund_invoice_number',
        'fbr_refund_qr_code',
        'fbr_refund_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => PriceCast::class,
            'tax' => PriceCast::class,
            'discount' => PriceCast::class,
            'discount_percentage' => 'decimal:6',
            'freight_fare' => PriceCast::class,
            'total' => PriceCast::class,
            'status' => SaleStatus::class,
            'payment_status' => SalePaymentStatus::class,
            'payment_method' => SalePaymentMethod::class,
            'use_fbr' => 'boolean',
            'paid_at' => 'datetime',
            'fbr_synced_at' => 'datetime',
            'fbr_response' => 'array',
            'fbr_refund_synced_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class)
            ->using(SaleVariation::class)
            ->withPivot('stock_id', 'description', 'quantity', 'unit_price', 'tax', 'discount', 'discount_type', 'discount_percentage', 'total', 'supplier_price', 'supplier_total', 'is_preparable');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function preparableItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SalePreparableItem::class);
    }

    public function currencyMultiplier(): int
    {
        $this->loadMissing('store.currency');
        $decimalPlaces = $this->store?->currency?->decimal_places ?? 2;
        $multiplier = (int) pow(10, $decimalPlaces);

        return $multiplier > 0 ? $multiplier : 1;
    }

    public function buildReceiptLines(): Collection
    {
        $this->loadMissing([
            'preparableItems.variation',
            'store.currency',
            'variations.unit',
        ]);

        $currencyMultiplier = $this->currencyMultiplier();

        $allPreparableItems = $this->preparableItems
            ->groupBy(fn ($item) => $item->preparable_variation_id.'_'.$item->sequence);

        $groupedVariations = $this->variations
            ->groupBy(fn ($variation) => ($variation->pivot->is_preparable ?? false) ? 'preparable' : 'regular')
            ->map(function ($items, $type) use ($allPreparableItems) {
                if ($type === 'preparable') {
                    $sequenceByVariationId = [];

                    return $items->map(function ($variation) use ($allPreparableItems, &$sequenceByVariationId) {
                        $variationId = $variation->id;
                        if (! isset($sequenceByVariationId[$variationId])) {
                            $sequenceByVariationId[$variationId] = 0;
                        }
                        $sequence = $sequenceByVariationId[$variationId];
                        $sequenceByVariationId[$variationId]++;

                        $key = $variationId.'_'.$sequence;
                        $preparableItems = $allPreparableItems->get($key, collect());

                        $pivot = $variation->pivot;
                        $preparableQty = (float) ($pivot->quantity ?? 1);
                        $preparableDiscount = (float) ($pivot->discount ?? 0);
                        $preparableTax = (float) ($pivot->tax ?? 0);
                        $preparableTotal = (float) ($pivot->total ?? 0);

                        $nestedItemsTax = $preparableItems->sum(fn ($item) => (float) ($item->tax ?? 0) * (float) ($item->quantity ?? 1));
                        $nestedItemsDiscount = $preparableItems->sum(fn ($item) => (float) ($item->discount ?? 0));

                        $combinedTotal = $preparableTotal;
                        $combinedTax = ($preparableTax * $preparableQty) + $nestedItemsTax;
                        $combinedDiscount = $preparableDiscount + $nestedItemsDiscount;
                        $combinedUnitPrice = $preparableQty != 0 ? (($combinedTotal + $combinedDiscount) / $preparableQty) : 0;

                        $description = $pivot->description ?? ($variation->brand_name
                            ? $variation->sku.' - '.$variation->brand_name.' - '.$variation->description
                            : $variation->sku.' - '.$variation->description);

                        $nestedDescriptions = $preparableItems->map(function ($item) {
                            if (! $item->variation) {
                                return 'Unknown';
                            }
                            $itemVariation = $item->variation;
                            $itemDesc = $itemVariation->brand_name
                                ? $itemVariation->sku.' - '.$itemVariation->brand_name.' - '.$itemVariation->description
                                : $itemVariation->sku.' - '.$itemVariation->description;
                            $itemQty = (float) $item->quantity;
                            $itemQtyFormatted = number_format($itemQty, 6, '.', ',');
                            $itemQtyFormatted = rtrim(rtrim($itemQtyFormatted, '0'), '.') ?: '0';

                            return $itemDesc.' (Qty: '.$itemQtyFormatted.')';
                        })->filter()->implode(', ');

                        if ($nestedDescriptions) {
                            $description .= ' ['.rtrim($nestedDescriptions, ', ').']';
                        }

                        return [
                            'variation' => $variation,
                            'quantity' => $preparableQty,
                            'unit_price' => $combinedUnitPrice,
                            'line_tax_total' => $combinedTax,
                            'line_discount' => $combinedDiscount,
                            'line_total' => $combinedTotal,
                            'discount_type' => $pivot->discount_type ?? null,
                            'discount_percentage' => $pivot->discount_percentage ?? null,
                            'is_preparable' => true,
                            'description' => $description,
                        ];
                    });
                }

                return $items->groupBy(fn ($variation) => $variation->pivot->description ?? $variation->id)
                    ->map(function ($groupedItems) {
                        $first = $groupedItems->first();
                        $qty = $groupedItems->sum(fn ($row) => (float) ($row->pivot->quantity ?? 0));
                        $unitPrice = (float) ($first->pivot->unit_price ?? 0);
                        $lineTaxTotal = $groupedItems->sum(fn ($row) => (float) ($row->pivot->tax ?? 0) * (float) ($row->pivot->quantity ?? 0));
                        $lineDiscount = $groupedItems->sum(fn ($row) => (float) ($row->pivot->discount ?? 0));
                        $lineTotal = $groupedItems->sum(fn ($row) => (float) ($row->pivot->total ?? 0));

                        return [
                            'variation' => $first,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'line_tax_total' => $lineTaxTotal,
                            'line_discount' => $lineDiscount,
                            'line_total' => $lineTotal,
                            'discount_type' => $first->pivot->discount_type ?? null,
                            'discount_percentage' => $first->pivot->discount_percentage ?? null,
                            'is_preparable' => false,
                        ];
                    });
            })
            ->flatten(1)
            ->values();

        $customLines = DB::table('sale_variation')
            ->where('sale_id', $this->id)
            ->whereNull('variation_id')
            ->where('is_preparable', false)
            ->get()
            ->map(function ($row) {
                $quantity = (float) ($row->quantity ?? 0);
                $unitPrice = (float) ($row->unit_price ?? 0);
                $lineDiscount = (float) ($row->discount ?? 0);
                $lineTaxTotal = (float) ($row->tax ?? 0) * $quantity;
                $lineTotal = (float) ($row->total ?? 0);

                return [
                    'variation' => null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_tax_total' => $lineTaxTotal,
                    'line_discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'discount_type' => $row->discount_type ?? null,
                    'discount_percentage' => $row->discount_percentage ?? null,
                    'is_preparable' => false,
                    'description' => $row->description ?? 'Custom item',
                ];
            })
            ->map(function ($line) use ($currencyMultiplier) {
                $line['unit_price'] = (float) ($line['unit_price'] ?? 0) / $currencyMultiplier;
                $line['line_discount'] = (float) ($line['line_discount'] ?? 0) / $currencyMultiplier;
                $line['line_tax_total'] = (float) ($line['line_tax_total'] ?? 0) / $currencyMultiplier;
                $line['line_total'] = (float) ($line['line_total'] ?? 0) / $currencyMultiplier;

                return $line;
            });

        return $groupedVariations
            ->concat($customLines)
            ->values();
    }

    /**
     * Get total revenue for this sale (after all discounts)
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->total;
    }

    /**
     * Get total cost for this sale
     */
    public function getTotalCostAttribute(): float
    {
        $variationsCost = $this->variations->sum(function ($variation) {
            $pivot = $variation->pivot;

            return $pivot->supplier_total ?? 0;
        });

        $multiplier = $this->currencyMultiplier();

        $customCostRaw = DB::table('sale_variation')
            ->where('sale_id', $this->id)
            ->whereNull('variation_id')
            ->where('is_preparable', false)
            ->sum('supplier_total');

        if (! $customCostRaw) {
            $customCostRaw = DB::table('sale_variation')
                ->where('sale_id', $this->id)
                ->whereNull('variation_id')
                ->where('is_preparable', false)
                ->sum('total');
        }

        $customCost = (float) $customCostRaw / $multiplier;

        return $variationsCost + $customCost;
    }

    /**
     * Get total profit for this sale
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * Get profit margin percentage for this sale
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_revenue == 0) {
            return 0;
        }

        return ($this->total_profit / $this->total_revenue) * 100;
    }

    /**
     * Get total quantity sold in this sale
     */
    public function getTotalQuantitySoldAttribute(): float
    {
        $variationsQuantity = $this->variations->sum('pivot.quantity');
        $customQuantity = DB::table('sale_variation')
            ->where('sale_id', $this->id)
            ->whereNull('variation_id')
            ->where('is_preparable', false)
            ->sum('quantity');

        return (float) $variationsQuantity + (float) $customQuantity;
    }
}
