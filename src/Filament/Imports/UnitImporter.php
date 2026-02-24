<?php

namespace SmartTill\Core\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use SmartTill\Core\Models\Unit;
use SmartTill\Core\Models\UnitDimension;

class UnitImporter extends Importer
{
    protected static ?string $model = Unit::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Name')
                ->exampleHeader('Name')
                ->example('Kilogram'),
            ImportColumn::make('symbol')
                ->label('Symbol')
                ->exampleHeader('Symbol')
                ->example(['pc', 'kg', 'g', 'L', 'ml']),
            ImportColumn::make('dimension')
                ->label('Dimension')
                ->exampleHeader('Dimension')
                ->example(['mass', 'volume', 'length', 'count']),
            ImportColumn::make('code')
                ->label('Code')
                ->exampleHeader('Code')
                ->example(['kg', 'g']),
            ImportColumn::make('to_base_factor')
                ->label('To Base Factor')
                ->exampleHeader('To Base Factor')
                ->example(['1', '1000']),
            ImportColumn::make('to_base_offset')
                ->label('To Base Offset')
                ->exampleHeader('To Base Offset')
                ->example(['0', '273.15']),
        ];
    }

    public function resolveRecord(): Unit
    {
        $storeId = $this->options['store_id'] ?? Filament::getTenant()?->getKey();

        if (blank($storeId)) {
            throw ValidationException::withMessages([
                'name' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
            ]);
        }

        // Find or create the record
        $record = Unit::firstOrNew([
            'name' => $this->data['name'],
            'store_id' => $storeId,
        ]);

        $dimension = null;
        $dimensionValue = trim((string) ($this->data['dimension'] ?? ''));
        if ($dimensionValue !== '') {
            $dimension = UnitDimension::query()
                ->where('name', $dimensionValue)
                ->first();

            if (! $dimension) {
                throw ValidationException::withMessages([
                    'dimension' => "Unknown dimension: {$dimensionValue}.",
                ]);
            }
        }

        if (! $record->exists && ! $dimension) {
            throw ValidationException::withMessages([
                'dimension' => 'Dimension is required for new units.',
            ]);
        }

        // Explicitly set store_id to ensure it's never null
        $record->store_id = $storeId;
        $record->dimension_id = $dimension?->id ?? $record->dimension_id;
        $record->code = $this->data['code'] ?? $record->code;
        $record->to_base_factor = $this->data['to_base_factor'] ?? $record->to_base_factor ?? 1;
        $record->to_base_offset = $this->data['to_base_offset'] ?? $record->to_base_offset ?? 0;

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your unit import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
