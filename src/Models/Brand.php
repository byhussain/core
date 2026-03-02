<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartTill\Core\Enums\BrandStatus;
use SmartTill\Core\Traits\HasStoreScopedReference;
use SmartTill\Core\Traits\TracksUserActivity;

class Brand extends Model
{
    use HasFactory, HasStoreScopedReference, SoftDeletes, TracksUserActivity;

    protected $fillable = ['store_id', 'name', 'description'];

    protected function casts(): array
    {
        return [
            'status' => BrandStatus::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get total revenue for this brand from all sales
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->products()
            ->with(['variations.sales'])
            ->get()
            ->sum(function ($product) {
                return $product->variations->sum(function ($variation) {
                    return $variation->sales->sum(function ($sale) {
                        $pivot = $sale->pivot;

                        return $pivot->total ?? 0;
                    });
                });
            });
    }

    /**
     * Get total cost for this brand from all sales
     */
    public function getTotalCostAttribute(): float
    {
        return $this->products()
            ->with(['variations.sales'])
            ->get()
            ->sum(function ($product) {
                return $product->variations->sum(function ($variation) {
                    return $variation->sales->sum(function ($sale) {
                        $pivot = $sale->pivot;

                        return $pivot->supplier_total ?? 0;
                    });
                });
            });
    }

    /**
     * Get total profit for this brand
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * Get profit margin percentage for this brand
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->total_revenue == 0) {
            return 0;
        }

        return ($this->total_profit / $this->total_revenue) * 100;
    }

    /**
     * Get total quantity sold for this brand
     */
    public function getTotalQuantitySoldAttribute(): float
    {
        return $this->products()
            ->with(['variations.sales'])
            ->get()
            ->sum(function ($product) {
                return $product->variations->sum(function ($variation) {
                    return $variation->sales->sum('pivot.quantity');
                });
            });
    }
}
