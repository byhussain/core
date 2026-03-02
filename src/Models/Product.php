<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Enums\ProductStatus;
use SmartTill\Core\Observers\ProductObserver;
use SmartTill\Core\Traits\HasStoreScopedReference;
use SmartTill\Core\Traits\TracksUserActivity;

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasStoreScopedReference, SoftDeletes, TracksUserActivity;

    protected $fillable = [
        'store_id',
        'brand_id',
        'category_id',
        'name',
        'description',
        'has_variations',
        'is_preparable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'has_variations' => 'boolean',
            'is_preparable' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }

    /**
     * Get total revenue for this product from all sales
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->variations->sum(function ($variation) {
            return $variation->sales->sum(function ($sale) {
                $pivot = $sale->pivot;

                return $pivot->total ?? 0;
            });
        });
    }

    /**
     * Get total cost for this product from all sales
     */
    public function getTotalCostAttribute(): float
    {
        return $this->variations->sum(function ($variation) {
            return $variation->sales->sum(function ($sale) {
                $pivot = $sale->pivot;

                return $pivot->supplier_total ?? 0;
            });
        });
    }

    /**
     * Get total profit for this product
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * Get profit margin percentage for this product
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_revenue == 0) {
            return 0;
        }

        return ($this->total_profit / $this->total_revenue) * 100;
    }

    /**
     * Get total quantity sold for this product
     */
    public function getTotalQuantitySoldAttribute(): float
    {
        return $this->variations->sum(function ($variation) {
            return $variation->sales->sum('pivot.quantity');
        });
    }
}
