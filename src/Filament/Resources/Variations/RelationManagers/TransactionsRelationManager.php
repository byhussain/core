<?php

namespace SmartTill\Core\Filament\Resources\Variations\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

                Filter::make('created_at_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $data['until'])
                            );
                    }),
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
