<?php

namespace SmartTill\Core\Filament\Resources\Variations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Variations\Pages\EditVariation;
use SmartTill\Core\Filament\Resources\Variations\Pages\ListVariations;
use SmartTill\Core\Filament\Resources\Variations\Pages\ViewVariation;
use SmartTill\Core\Filament\Resources\Variations\RelationManagers\StocksRelationManager;
use SmartTill\Core\Filament\Resources\Variations\RelationManagers\TransactionsRelationManager;
use SmartTill\Core\Filament\Resources\Variations\Schemas\VariationForm;
use SmartTill\Core\Filament\Resources\Variations\Schemas\VariationInfolist;
use SmartTill\Core\Filament\Resources\Variations\Tables\VariationsTable;
use SmartTill\Core\Models\Variation;
use UnitEnum;

class VariationResource extends Resource
{
    protected static ?string $model = Variation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Cube;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'description';

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Variations');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Variations');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Variations');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Variations');
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return 'New';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return VariationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VariationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VariationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product.brand', 'product.category'])
            ->withBarcodeStock();
    }

    public static function getRelations(): array
    {
        return [
            StocksRelationManager::class,
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVariations::route('/'),
            'view' => ViewVariation::route('/{record}'),
            'edit' => EditVariation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
