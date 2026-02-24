<?php

namespace SmartTill\Core\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Enums\BrandStatus;
use SmartTill\Core\Models\Brand;

class BrandImporter extends Importer
{
    protected static ?string $model = Brand::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Name')
                ->exampleHeader('Name')
                ->example(['Nestlé', 'Coca-Cola', 'PepsiCo']),
            ImportColumn::make('description')
                ->label('Description')
                ->exampleHeader('Description')
                ->requiredMapping()
                ->example('Freshly baked bread, cakes, and pastries daily'),
            ImportColumn::make('status')
                ->label('Status')
                ->exampleHeader('Status')
                ->example(['active', 'inactive', 'draft'])
                ->requiredMapping()
                ->rules(['required', new Enum(BrandStatus::class)]),
        ];
    }

    public function resolveRecord(): ?Brand
    {
        $storeId = $this->options['store_id'] ?? Filament::getTenant()?->getKey();

        if (blank($storeId)) {
            throw ValidationException::withMessages([
                'name' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
            ]);
        }

        // Find or create the record
        $record = Brand::firstOrNew([
            'name' => $this->data['name'],
            'store_id' => $storeId,
        ]);

        // Explicitly set store_id to ensure it's never null
        $record->store_id = $storeId;

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your brand import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
