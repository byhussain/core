<?php

namespace SmartTill\Core\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use SmartTill\Core\Models\Transaction;

class CustomerTransactionExporter extends BaseStoreExporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('referenceable_id')
                ->label('Reference')
                ->prefix(fn ($record) => ! empty($record->referenceable_type) ? class_basename($record->referenceable_type).' #' : '—'),
            ExportColumn::make('note'),
            ExportColumn::make('type'),
            ExportColumn::make('amount'),
            ExportColumn::make('amount_balance'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    protected function getExportTypeName(): string
    {
        return 'Customer-Transactions';
    }
}
