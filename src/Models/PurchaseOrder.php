<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Enums\PurchaseOrderStatus;
use SmartTill\Core\Traits\HasStoreScopedReference;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory, HasStoreScopedReference, SoftDeletes;

    protected $fillable = [
        'store_id',
        'supplier_id',
        'reference',
        'status',
        'total_requested_quantity',
        'total_received_quantity',
        'total_requested_unit_price',
        'total_received_unit_price',
        'total_requested_tax_amount',
        'total_received_tax_amount',
        'total_requested_supplier_price',
        'total_received_supplier_price',
        'total_requested_supplier_percentage',
        'total_received_supplier_percentage',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'total_requested_quantity' => 'float',
            'total_received_quantity' => 'float',
            'total_requested_unit_price' => PriceCast::class,
            'total_received_unit_price' => PriceCast::class,
            'total_requested_tax_amount' => PriceCast::class,
            'total_received_tax_amount' => PriceCast::class,
            'total_requested_supplier_price' => PriceCast::class,
            'total_received_supplier_price' => PriceCast::class,
            'total_requested_supplier_percentage' => 'decimal:6',
            'total_received_supplier_percentage' => 'decimal:6',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class)
            ->using(PurchaseOrderProduct::class)
            ->withPivot(
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
            )
            ->withTimestamps();
    }

    public function purchaseOrderProducts(): HasMany
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing(['purchaseOrderProducts', 'store.currency']);
        $products = $this->purchaseOrderProducts;
        $decimalPlaces = $this->store?->currency?->decimal_places ?? 2;

        $totalRequestedQuantity = (float) $products->count();
        $totalReceivedQuantity = (float) $products->filter(fn ($product) => (float) ($product->received_quantity ?? 0) > 0)->count();

        $totalRequestedUnitPrice = (float) $products->sum(fn ($p) => (float) ($p->requested_quantity ?? 0) * (float) ($p->requested_unit_price ?? 0));
        $totalRequestedSupplierPrice = $this->calculateRequestedSupplierTotal();
        $totalRequestedTaxAmount = (float) $products->sum('requested_tax_amount');

        $totalReceivedUnitPrice = (float) $products->sum(fn ($p) => (float) ($p->received_quantity ?? 0) * (float) ($p->received_unit_price ?? 0));
        $totalReceivedSupplierPrice = $this->calculateReceivedSupplierTotal();
        $totalReceivedTaxAmount = (float) $products->sum('received_tax_amount');

        $this->forceFill([
            'total_requested_quantity' => round($totalRequestedQuantity, 2),
            'total_received_quantity' => round($totalReceivedQuantity, 2),
            'total_requested_unit_price' => round($totalRequestedUnitPrice, $decimalPlaces),
            'total_received_unit_price' => round($totalReceivedUnitPrice, $decimalPlaces),
            'total_requested_tax_amount' => round($totalRequestedTaxAmount, $decimalPlaces),
            'total_received_tax_amount' => round($totalReceivedTaxAmount, $decimalPlaces),
            'total_requested_supplier_price' => round($totalRequestedSupplierPrice, $decimalPlaces),
            'total_received_supplier_price' => round($totalReceivedSupplierPrice, $decimalPlaces),
        ])->saveQuietly();
    }

    public function calculateRequestedSupplierTotal(): float
    {
        $this->loadMissing(['purchaseOrderProducts', 'store.currency']);

        return round(
            $this->purchaseOrderProducts->sum(fn ($product) => self::calculateLineSupplierTotal(
                quantity: (float) ($product->requested_quantity ?? 0),
                unitPrice: (float) ($product->requested_unit_price ?? 0),
                supplierPrice: is_numeric($product->requested_supplier_price) ? (float) $product->requested_supplier_price : null,
                supplierPercentage: is_numeric($product->requested_supplier_percentage) ? (float) $product->requested_supplier_percentage : null,
                inputIsPercentage: $product->requested_supplier_is_percentage,
            )),
            $this->store?->currency?->decimal_places ?? 2,
        );
    }

    public function calculateReceivedSupplierTotal(): float
    {
        $this->loadMissing(['purchaseOrderProducts', 'store.currency']);

        return round(
            $this->purchaseOrderProducts->sum(fn ($product) => self::calculateLineSupplierTotal(
                quantity: (float) ($product->received_quantity ?? 0),
                unitPrice: (float) ($product->received_unit_price ?? 0),
                supplierPrice: is_numeric($product->received_supplier_price) ? (float) $product->received_supplier_price : null,
                supplierPercentage: is_numeric($product->received_supplier_percentage) ? (float) $product->received_supplier_percentage : null,
                inputIsPercentage: $product->received_supplier_is_percentage,
            )),
            $this->store?->currency?->decimal_places ?? 2,
        );
    }

    private static function calculateLineSupplierTotal(
        float $quantity,
        float $unitPrice,
        ?float $supplierPrice,
        ?float $supplierPercentage,
        ?bool $inputIsPercentage
    ): float {
        if ($quantity <= 0) {
            return 0.0;
        }

        if ($inputIsPercentage === true && is_numeric($supplierPercentage) && $unitPrice > 0) {
            return $quantity * ($unitPrice - ($unitPrice * ($supplierPercentage / 100)));
        }

        if ($inputIsPercentage === false && is_numeric($supplierPrice)) {
            return $quantity * $supplierPrice;
        }

        if (is_numeric($supplierPercentage) && $unitPrice > 0) {
            return $quantity * ($unitPrice - ($unitPrice * ($supplierPercentage / 100)));
        }

        if (is_numeric($supplierPrice)) {
            return $quantity * $supplierPrice;
        }

        return 0.0;
    }
}
