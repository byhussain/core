<?php

namespace SmartTill\Core\Services;

use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Variation;

/**
 * Creates the single default zero-quantity stock row that every variation
 * should own. Used both by the VariationObserver (for freshly-created
 * variations) and by the backfill command (for legacy variations that were
 * created before this behaviour existed), so the two paths can never drift.
 */
class VariationDefaultStockService
{
    /**
     * Create the default stock row for a variation that has none yet.
     * Returns the created Stock, or null when the variation already has one.
     */
    public function createFor(Variation $variation): ?Stock
    {
        if ($variation->stocks()->exists()) {
            return null;
        }

        $stock = $variation->stocks()->create([
            'barcode' => $this->generateUniqueEan13($variation),
            'batch_number' => null,
            'stock' => 0,
            'unit_id' => $variation->unit_id,
        ]);

        // Copy the variation's base price exactly. We write the raw minor-unit
        // integer rather than the cast float because a Stock has no store_id:
        // outside a Filament tenant context PriceCast would fall back to a
        // 2-decimal multiplier and corrupt the value for other currencies.
        // Read it from the live attribute bag (getAttributes) rather than
        // getRawOriginal — during the "created" observer event the original
        // has not been synced yet, so getRawOriginal would return null.
        // A direct query-builder update also avoids re-triggering StockObserver.
        $rawPrice = $variation->getAttributes()['price'] ?? null;
        if ($rawPrice !== null) {
            Stock::query()->whereKey($stock->getKey())->update(['price' => $rawPrice]);
            $stock->setRawAttributes(array_merge($stock->getAttributes(), ['price' => $rawPrice]), true);
        }

        return $stock;
    }

    /**
     * Generate an EAN-13 barcode that is unique for this variation (the stocks
     * table is unique on variation_id + barcode + batch_number).
     */
    private function generateUniqueEan13(Variation $variation): string
    {
        do {
            $base = str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            $barcode = $base.$this->ean13Checksum($base);
        } while ($variation->stocks()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    private function ean13Checksum(string $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $num = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $num : $num * 3;
        }

        $mod = $sum % 10;

        return $mod === 0 ? 0 : (10 - $mod);
    }
}
