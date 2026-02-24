<?php

namespace SmartTill\Core\Observers;

use Illuminate\Support\Facades\Cache;
use SmartTill\Core\Models\Variation;

class VariationObserver
{
    public function creating(Variation $variation): void
    {
        $this->populateStoreAndBrand($variation);
        $this->recalculate($variation);
    }

    public function created(Variation $variation): void
    {
        $this->invalidateProductSearchCache($variation->store_id);
        $this->invalidateVariationCache($variation);
    }

    public function updating(Variation $variation): void
    {
        $this->populateStoreAndBrand($variation);
        $this->recalculate($variation);
    }

    public function updated(Variation $variation): void
    {
        $this->invalidateProductSearchCache($variation->store_id);
        $this->invalidateVariationCache($variation);
    }

    public function deleted(Variation $variation): void
    {
        $storeId = $variation->store_id ?? $variation->product?->store_id;
        if ($storeId) {
            $this->invalidateProductSearchCache($storeId);
        }
        $this->invalidateVariationCache($variation);
    }

    protected function populateStoreAndBrand(Variation $variation): void
    {
        if ($variation->product_id && ! $variation->product) {
            $variation->load('product.brand');
        }

        if ($variation->product) {
            $variation->store_id = $variation->product->store_id;
            $variation->brand_name = $variation->product->brand?->name;
        }
    }

    protected function recalculate(Variation $variation): void
    {
        $price = $variation->price; // already cast
        if (! is_numeric($price) || $price <= 0) {
            return;
        }

        // Determine dirtiness once
        $salePriceDirty = $variation->isDirty('sale_price');
        $salePercentageDirty = $variation->isDirty('sale_percentage');

        // Sale (keep negative values to represent loss)
        if ($salePriceDirty && is_numeric($variation->sale_price)) {
            $salePrice = (float) $variation->sale_price; // already cast
            $variation->sale_percentage = (($price - $salePrice) / $price) * 100;
        } elseif ($salePercentageDirty && is_numeric($variation->sale_percentage)) {
            $p = (float) $variation->sale_percentage;
            $variation->sale_price = $price * (1 - $p / 100);
        }

        // If both sale_price and sale_percentage are null, empty, or zero, set defaults
        $salePriceIsNullOrZero = is_null($variation->sale_price) || $variation->sale_price === '' || (is_numeric($variation->sale_price) && (float) $variation->sale_price == 0.0);
        $salePercentageIsNullOrZero = is_null($variation->sale_percentage) || $variation->sale_percentage === '' || (is_numeric($variation->sale_percentage) && (float) $variation->sale_percentage == 0.0);
        if ($salePriceIsNullOrZero && $salePercentageIsNullOrZero) {
            $variation->sale_price = $price;
            $variation->sale_percentage = 0;
        }
    }

    /**
     * Invalidate product search cache for a specific store.
     */
    protected function invalidateProductSearchCache(int $storeId): void
    {
        $cacheVersionKey = "product_search_version_{$storeId}";
        // Initialize key if it doesn't exist (atomic operation with Redis)
        Cache::add($cacheVersionKey, 0, now()->addYears(10));
        // Increment version to invalidate cache
        Cache::increment($cacheVersionKey);
    }

    /**
     * Invalidate individual variation cache.
     */
    protected function invalidateVariationCache(Variation $variation): void
    {
        Cache::forget("variation_{$variation->id}");
    }
}
