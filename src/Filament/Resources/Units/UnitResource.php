<?php

namespace SmartTill\Core\Filament\Resources\Units;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Units\Pages\CreateUnit;
use SmartTill\Core\Filament\Resources\Units\Pages\EditUnit;
use SmartTill\Core\Filament\Resources\Units\Pages\ListUnits;
use SmartTill\Core\Filament\Resources\Units\Schemas\UnitForm;
use SmartTill\Core\Filament\Resources\Units\Tables\UnitsTable;
use SmartTill\Core\Models\Unit;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Scale;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Units');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Units');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Units');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Units');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Units')
            && self::isStoreOwnedRecord($record);
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Units')
            && self::isStoreOwnedRecord($record);
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Units')
            && self::isStoreOwnedRecord($record);
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Units')
            && self::isStoreOwnedRecord($record);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'name', 'symbol'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->reference ?: ($record->local_id ?: "#{$record->id}"),
            'Symbol' => $record->symbol,
            'Type' => $record->type,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $storeId = \Filament\Facades\Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->forStoreOrGlobal($storeId);
    }

    protected static function isStoreOwnedRecord(Model $record): bool
    {
        $storeId = \Filament\Facades\Filament::getTenant()?->getKey();

        return $storeId !== null && (int) $record->store_id === (int) $storeId;
    }
}
