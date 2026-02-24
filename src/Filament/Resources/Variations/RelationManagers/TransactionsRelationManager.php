<?php

namespace SmartTill\Core\Filament\Resources\Variations\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use SmartTill\Core\Filament\Exports\ProductTransactionExporter;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Transactions\Tables\TransactionsTable;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Variation Transactions');
    }

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'product_stock_in' => 'Stock-in',
                        'product_stock_out' => 'Stock-out',
                        'variation_stock_in' => 'Variation Stock-in',
                        'variation_stock_out' => 'Variation Stock-out',
                    ])
                    ->multiple()
                    ->preload(),

                DateRangeFilter::make('created_at')
                    ->label('Date range'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ProductTransactionExporter::class)
                        ->visible(fn () => ResourceCanAccessHelper::check('Export Variations'))
                        ->authorize(fn () => ResourceCanAccessHelper::check('Export Variations')),
                ]),
            ]);
    }
}
