<?php

namespace SmartTill\Core\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use SmartTill\Core\Models\Unit;

class UnitExporter extends BaseStoreExporter
{
    protected static ?string $model = Unit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('symbol'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your unit export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    protected function getExportTypeName(): string
    {
        return 'Units';
    }
}
