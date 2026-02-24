<?php

namespace SmartTill\Core\Observers;

use Illuminate\Support\Facades\Cache;
use SmartTill\Core\Models\Stock;

class StockObserver
{
    public function created(Stock $barcode): void
    {
        $this->handleStockChange($barcode, $barcode->stock ?? 0, 'Manual stock updated');
        $this->invalidateCaches($barcode);
    }

    public function updated(Stock $barcode): void
    {
        if ($barcode->isDirty('stock')) {
            $delta = (float) $barcode->stock - (float) $barcode->getOriginal('stock');
            $this->handleStockChange($barcode, $delta, 'Manual stock updated');
        }

        $this->invalidateCaches($barcode);
    }

    protected function handleStockChange(Stock $barcode, float $delta, string $note): void
    {
        if ($delta == 0.0) {
            return;
        }

        $variation = $barcode->variation;
        if (! $variation) {
            return;
        }

        $lastBalance = $variation->transactions()->latest('id')->value('quantity_balance') ?? 0;
        $newBalance = $lastBalance + $delta;

        $variation->transactions()->create([
            'store_id' => $variation->store_id,
            'type' => $delta > 0 ? 'variation_stock_in' : 'variation_stock_out',
            'quantity' => $delta,
            'note' => $note,
            'meta' => [
                'stock_id' => $barcode->id,
                'barcode' => $barcode->barcode,
                'batch_number' => $barcode->batch_number,
            ],
            'quantity_balance' => $newBalance,
        ]);
    }

    protected function invalidateCaches(Stock $barcode): void
    {
        $variation = $barcode->variation;
        if (! $variation) {
            return;
        }

        $storeId = $variation->store_id;
        if ($storeId) {
            $cacheVersionKey = "product_search_version_{$storeId}";
            Cache::add($cacheVersionKey, 0, now()->addYears(10));
            Cache::increment($cacheVersionKey);
        }

        $variation->invalidateCache();
    }
}
