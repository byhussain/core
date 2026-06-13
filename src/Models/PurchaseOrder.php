<?php

namespace SmartTill\Core\Models;

use App\Models\Store;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Casts\PriceCast;
use SmartTill\Core\Enums\PurchaseOrderStatus;
use SmartTill\Core\Support\CloudSyncFlagger;
use SmartTill\Core\Traits\HasStoreScopedReference;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
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
        'withholding_tax_is_percentage',
        'withholding_tax_value',
        'withholding_tax_amount',
        'discount_is_percentage',
        'discount_value',
        'discount_amount',
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
            'withholding_tax_is_percentage' => 'boolean',
            'withholding_tax_value' => 'decimal:6',
            'withholding_tax_amount' => PriceCast::class,
            'discount_is_percentage' => 'boolean',
            'discount_value' => 'decimal:6',
            'discount_amount' => PriceCast::class,
        ];
    }

    /**
     * Compute the withholding tax amount for a given supplier-cost base.
     * When the value is a percentage it is applied to the base; otherwise the
     * flat value is returned as-is. Returns 0 when no withholding tax is set.
     */
    public function computeWithholdingTax(float $supplierBase): float
    {
        $value = (float) ($this->withholding_tax_value ?? 0);

        if ($value <= 0) {
            return 0.0;
        }

        return $this->withholding_tax_is_percentage
            ? $supplierBase * ($value / 100)
            : $value;
    }

    /**
     * Compute the overall invoice discount for a given base (the supplier
     * subtotal plus withholding tax). When the value is a percentage it is
     * applied to the base; otherwise the flat value is returned. The discount
     * is never larger than the base. Returns 0 when no discount is set.
     */
    public function computeDiscount(float $baseAfterWithholding): float
    {
        $value = (float) ($this->discount_value ?? 0);

        if ($value <= 0) {
            return 0.0;
        }

        $discount = $this->discount_is_percentage
            ? $baseAfterWithholding * ($value / 100)
            : $value;

        return min(max($discount, 0.0), max($baseAfterWithholding, 0.0));
    }

    /**
     * The order's grand total: supplier subtotal + withholding tax − discount.
     */
    public function grandTotalFor(float $supplierBase): float
    {
        if ($supplierBase <= 0) {
            return 0.0;
        }

        $afterWithholding = $supplierBase + $this->computeWithholdingTax($supplierBase);

        return $afterWithholding - $this->computeDiscount($afterWithholding);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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

        // Withholding tax is computed on the supplier subtotal. Once anything
        // has been received we base it on the received subtotal (that is what
        // gets posted to the supplier ledger); otherwise we preview it on the
        // requested subtotal.
        $withholdingBase = $totalReceivedSupplierPrice > 0
            ? $totalReceivedSupplierPrice
            : $totalRequestedSupplierPrice;
        $withholdingTaxAmount = $this->computeWithholdingTax($withholdingBase);

        // The overall invoice discount is applied AFTER withholding tax, i.e. on
        // (supplier subtotal + withholding tax).
        $discountAmount = $this->computeDiscount($withholdingBase + $withholdingTaxAmount);

        $this->forceFill([
            'total_requested_quantity' => round($totalRequestedQuantity, 2),
            'total_received_quantity' => round($totalReceivedQuantity, 2),
            'total_requested_unit_price' => round($totalRequestedUnitPrice, $decimalPlaces),
            'total_received_unit_price' => round($totalReceivedUnitPrice, $decimalPlaces),
            'total_requested_tax_amount' => round($totalRequestedTaxAmount, $decimalPlaces),
            'total_received_tax_amount' => round($totalReceivedTaxAmount, $decimalPlaces),
            'total_requested_supplier_price' => round($totalRequestedSupplierPrice, $decimalPlaces),
            'total_received_supplier_price' => round($totalReceivedSupplierPrice, $decimalPlaces),
            'withholding_tax_amount' => round($withholdingTaxAmount, $decimalPlaces),
            'discount_amount' => round($discountAmount, $decimalPlaces),
        ])->saveQuietly();

        CloudSyncFlagger::flag($this);
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
