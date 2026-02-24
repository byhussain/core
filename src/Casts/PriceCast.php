<?php

namespace SmartTill\Core\Casts;

use App\Models\Store;
use Filament\Facades\Filament;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PriceCast implements CastsAttributes
{
    /**
     * Get the multiplier based on currency decimal places
     *
     * @param  array<string, mixed>  $attributes
     */
    private function getMultiplier(Model $model, array $attributes = []): int
    {
        $store = $this->resolveStore($model, $attributes);

        if (! $store || ! $store->currency) {
            // Fallback to 100 (2 decimals) for backward compatibility
            return 100;
        }

        $currency = $store->currency;
        $decimalPlaces = $currency->decimal_places ?? 2;

        // Calculate multiplier: 10^decimal_places
        return (int) pow(10, $decimalPlaces);
    }

    /**
     * Resolve the store from the model
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolveStore(Model $model, array $attributes = []): ?Store
    {
        // First, try to get store from Filament panel (tenant)
        try {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Store) {
                return $tenant->load('currency');
            }
        } catch (\Exception $e) {
            // Not in Filament context, continue with other methods
        }

        // Check attributes array (for new models being created)
        if (isset($attributes['store_id']) && $attributes['store_id']) {
            $storeId = $attributes['store_id'];

            return Cache::remember("store_currency_{$storeId}", 3600, function () use ($storeId) {
                return Store::with('currency')->find($storeId);
            });
        }

        // Check if model has store_id attribute
        if (isset($model->store_id) && $model->store_id) {
            $storeId = $model->store_id;

            return Cache::remember("store_currency_{$storeId}", 3600, function () use ($storeId) {
                return Store::with('currency')->find($storeId);
            });
        }

        // For pivot models (like SaleVariation), try to get store_id from sale relationship or sale_id attribute
        // Check attributes first (available during creation)
        if (isset($attributes['sale_id']) && $attributes['sale_id']) {
            $saleId = $attributes['sale_id'];

            return Cache::remember("sale_store_currency_{$saleId}", 3600, function () use ($saleId) {
                $sale = \SmartTill\Core\Models\Sale::with('store.currency')->find($saleId);

                return $sale?->store;
            });
        }

        // Check if model has sale_id attribute (available after creation or during attribute setting)
        // Try getAttributes() first (raw attributes), then check property
        $modelAttributes = $model->getAttributes();
        if (isset($modelAttributes['sale_id']) && $modelAttributes['sale_id']) {
            $saleId = $modelAttributes['sale_id'];

            return Cache::remember("sale_store_currency_{$saleId}", 3600, function () use ($saleId) {
                $sale = \SmartTill\Core\Models\Sale::with('store.currency')->find($saleId);

                return $sale?->store;
            });
        }

        // Also check if accessible as property
        if (property_exists($model, 'sale_id') || isset($model->sale_id)) {
            $saleId = $model->sale_id ?? null;
            if ($saleId) {
                return Cache::remember("sale_store_currency_{$saleId}", 3600, function () use ($saleId) {
                    $sale = \SmartTill\Core\Models\Sale::with('store.currency')->find($saleId);

                    return $sale?->store;
                });
            }
        }

        // Check if model has store relationship loaded
        if ($model->relationLoaded('store') && $model->store) {
            return $model->store;
        }

        // Try to load store relationship if it exists
        if (method_exists($model, 'store')) {
            try {
                $store = $model->store;
                if ($store) {
                    return $store;
                }
            } catch (\Exception $e) {
                // Relationship might not be available, ignore
            }
        }

        // For SaleVariation pivot, try to get store through sale relationship
        if (method_exists($model, 'sale')) {
            try {
                $sale = $model->sale;
                if ($sale && $sale->store) {
                    return $sale->store;
                }
            } catch (\Exception $e) {
                // Relationship might not be available, ignore
            }
        }

        return null;
    }

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        $multiplier = $this->getMultiplier($model, $attributes);

        return floatval($value) / $multiplier;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        $multiplier = $this->getMultiplier($model, $attributes);

        return round(floatval($value) * $multiplier);
    }
}
