<?php

namespace SmartTill\Core\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use SmartTill\Core\Models\Sale;

class SaleExporter extends BaseStoreExporter
{
    protected static ?string $model = Sale::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference')
                ->label('Reference'),
            ExportColumn::make('customer.name')
                ->label('Customer')
                ->default('Guest'),
            ExportColumn::make('customer.phone')
                ->label('Customer Phone'),
            ExportColumn::make('subtotal')
                ->label('Subtotal'),
            ExportColumn::make('tax')
                ->label('Tax'),
            ExportColumn::make('discount')
                ->label('Discount'),
            ExportColumn::make('total')
                ->label('Total'),
            ExportColumn::make('status')
                ->formatStateUsing(fn ($state): string => $state?->value ?? '—'),
            ExportColumn::make('payment_status')
                ->formatStateUsing(fn ($state): string => $state?->value ?? '—'),
            ExportColumn::make('paid_at')
                ->label('Paid At'),
            ExportColumn::make('created_at')
                ->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sales export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    protected function getExportTypeName(): string
    {
        return 'Sales';
    }
}
