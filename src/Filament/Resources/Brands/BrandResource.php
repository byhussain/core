<?php

namespace SmartTill\Core\Filament\Resources\Brands;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Brands\Pages\CreateBrand;
use SmartTill\Core\Filament\Resources\Brands\Pages\EditBrand;
use SmartTill\Core\Filament\Resources\Brands\Pages\ListBrands;
use SmartTill\Core\Filament\Resources\Brands\Pages\ViewBrand;
use SmartTill\Core\Filament\Resources\Brands\Schemas\BrandForm;
use SmartTill\Core\Filament\Resources\Brands\Schemas\BrandInfolist;
use SmartTill\Core\Filament\Resources\Brands\Tables\BrandsTable;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\Brand;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Tag;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Brands');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Brands');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Brands');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Brands');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Brands');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Brands');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Brands');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Brands');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'name', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->reference ?: ($record->local_id ?: "#{$record->id}"),
            'Description' => $record->description,
        ];
    }

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BrandInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'view' => ViewBrand::route('/{record}'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
