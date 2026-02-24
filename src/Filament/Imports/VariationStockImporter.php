<?php

namespace SmartTill\Core\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Enums\BatchIdentifier;
use SmartTill\Core\Models\Stock;
use SmartTill\Core\Models\Variation;

class VariationStockImporter extends Importer
{
    protected static ?string $model = Variation::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sku')
                ->label('SKU')
                ->exampleHeader('SKU')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('brand')
                ->label('Brand')
                ->exampleHeader('Brand')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('category')
                ->label('Category')
                ->exampleHeader('Category')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('description')
                ->label('Description')
                ->exampleHeader('Description')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('price')
                ->label('Price')
                ->exampleHeader('Price')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('supplier_price')
                ->label('Supplier Price')
                ->exampleHeader('Supplier Price')
                ->requiredMapping()
                ->rules(['nullable', 'string']),
            ImportColumn::make('tax_amount')
                ->label('Tax Amount')
                ->exampleHeader('Tax Amount')
                ->requiredMapping()
                ->rules(['nullable', 'string']),
            ImportColumn::make('stock')
                ->label('Stock')
                ->exampleHeader('Stock')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric']),
            ImportColumn::make('barcode')
                ->label('Barcode')
                ->exampleHeader('Barcode')
                ->requiredMapping()
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Variation
    {
        $storeId = $this->options['store_id'] ?? null;
        if (blank($storeId)) {
            throw ValidationException::withMessages([
                'stock' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
            ]);
        }

        $sku = trim((string) ($this->data['sku'] ?? ''));
        $brand = trim((string) ($this->data['brand'] ?? ''));
        $category = trim((string) ($this->data['category'] ?? ''));
        $description = trim((string) ($this->data['description'] ?? ''));
        if ($sku === '') {
            throw ValidationException::withMessages([
                'sku' => 'SKU is required.',
            ]);
        }

        $variations = Variation::query()
            ->where('store_id', $storeId)
            ->where('sku', $sku)
            ->where('description', $description)
            ->whereHas('product.brand', fn ($query) => $query->where('name', $brand))
            ->whereHas('product.category', fn ($query) => $query->where('name', $category))
            ->get();

        if ($variations->count() > 1) {
            throw ValidationException::withMessages([
                'sku' => 'Multiple variations found for this SKU. Please use a unique SKU.',
            ]);
        }

        return $variations->first();
    }

    public function fillRecord(): void
    {
        // Stock import should not update variation fields.
    }

    public function saveRecord(): void
    {
        // Skip saving variations; we only create stock rows in afterSave.
    }

    protected function afterSave(): void
    {
        /** @var Variation|null $variation */
        $variation = $this->record;
        if (! $variation) {
            throw ValidationException::withMessages([
                'sku' => 'Variation not found for the provided SKU.',
            ]);
        }

        $price = $this->parseNumericValue($this->data['price'] ?? null);
        $supplierInput = $this->data['supplier_price'] ?? null;
        $taxInput = $this->data['tax_amount'] ?? null;
        $stockValue = (float) ($this->data['stock'] ?? 0);
        $barcodeInput = trim((string) ($this->data['barcode'] ?? ''));

        $supplier = $this->parsePercentOrValue($supplierInput, $price, 'supplier');
        $tax = $this->parsePercentOrValue($taxInput, $price, 'tax');

        $batchNumber = $this->nextManualImportBatchNumber($variation->id);
        $barcodeValue = $barcodeInput !== '' ? $barcodeInput : $this->getLastBarcode($variation->id);
        if ($barcodeValue === null || $barcodeValue === '') {
            $barcodeValue = $this->generateFallbackBarcode();
        }

        Stock::create([
            'variation_id' => $variation->id,
            'barcode' => $barcodeValue,
            'batch_number' => $batchNumber,
            'price' => $price,
            'supplier_price' => $supplier['value'],
            'supplier_percentage' => $supplier['percent'],
            'tax_amount' => $tax['value'],
            'tax_percentage' => $tax['percent'],
            'stock' => $stockValue,
        ]);
    }

    private function parseNumericValue($value): float
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace(',', '', $raw);

        return round((float) $raw, 2);
    }

    private function parsePercentOrValue($value, float $basePrice, string $mode): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return ['value' => null, 'percent' => null];
        }

        $isPercent = str_contains($raw, '%');
        $number = str_replace('%', '', $raw);
        $number = str_replace(',', '', $number);
        $number = (float) $number;
        $number = round($number, $isPercent ? 6 : 2);

        if ($isPercent) {
            $percent = max(0.0, min(999.999999, $number));
            if ($mode === 'tax') {
                $amount = round($basePrice * ($percent / 100), 2);
            } else {
                $amount = round($basePrice * (1 - ($percent / 100)), 2);
            }

            return ['value' => $amount, 'percent' => $percent];
        }

        $amount = round($number, 2);
        if ($basePrice <= 0) {
            return ['value' => $amount, 'percent' => null];
        }

        if ($mode === 'tax') {
            $percent = ($amount / $basePrice) * 100;
        } else {
            $percent = (1 - ($amount / $basePrice)) * 100;
        }

        return ['value' => $amount, 'percent' => round($percent, 6)];
    }

    private function nextManualImportBatchNumber(int $variationId): string
    {
        $prefix = BatchIdentifier::ManualImport->value.'-';
        $max = Stock::query()
            ->where('variation_id', $variationId)
            ->where('batch_number', 'like', $prefix.'%')
            ->get(['batch_number'])
            ->reduce(function (int $carry, Stock $stock) use ($prefix): int {
                $batch = (string) $stock->batch_number;
                if (str_starts_with($batch, $prefix)) {
                    $number = (int) substr($batch, strlen($prefix));

                    return max($carry, $number);
                }

                return $carry;
            }, 0);

        return $prefix.($max + 1);
    }

    private function getLastBarcode(int $variationId): ?string
    {
        return Stock::query()
            ->where('variation_id', $variationId)
            ->latest('id')
            ->value('barcode');
    }

    private function generateFallbackBarcode(): string
    {
        do {
            $base = str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            $barcode = $base.$this->calculateEan13Checksum($base);
        } while (Stock::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    private function calculateEan13Checksum(string $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $num = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $num : $num * 3;
        }

        $mod = $sum % 10;

        return $mod === 0 ? 0 : (10 - $mod);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your stock import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
