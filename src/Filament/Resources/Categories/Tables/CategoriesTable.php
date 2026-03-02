<?php

namespace SmartTill\Core\Filament\Resources\Categories\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use SmartTill\Core\Filament\Exports\CategoryExporter;
use SmartTill\Core\Filament\Imports\CategoryImporter;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Helpers\SyncReferenceColumn;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SyncReferenceColumn::make(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('deleted_at')
                    ->label('Deleted at')
                    ->dateTime()
                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created at')
                    ->since()
                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->setTimezone(Filament::getTenant()?->timezone?->name ?? 'UTC')->format('M d, Y g:i A'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->since()
                    ->timezone(fn () => Filament::getTenant()?->timezone?->name ?? 'UTC')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->updated_at?->setTimezone(Filament::getTenant()?->timezone?->name ?? 'UTC')->format('M d, Y g:i A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(CategoryImporter::class)
                    ->options([
                        'store_id' => Filament::getTenant()?->getKey(),
                    ])
                    ->visible(fn () => ResourceCanAccessHelper::check('Import Categories'))
                    ->authorize(fn () => ResourceCanAccessHelper::check('Import Categories')),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View')
                        ->color('primary'),
                    EditAction::make()
                        ->label('Edit')
                        ->color('warning'),
                    DeleteAction::make()
                        ->label('Delete')
                        ->color('danger'),
                    RestoreAction::make()
                        ->label('Restore')
                        ->color('success'),
                    ForceDeleteAction::make()
                        ->label('Force delete')
                        ->color('warning'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(CategoryExporter::class)
                        ->visible(fn () => ResourceCanAccessHelper::check('Export Categories'))
                        ->authorize(fn () => ResourceCanAccessHelper::check('Export Categories')),
                ]),
            ]);
    }
}
