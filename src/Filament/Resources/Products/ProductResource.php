<?php

namespace SmartTill\Core\Filament\Resources\Products;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Products\Pages\CreateProduct;
use SmartTill\Core\Filament\Resources\Products\Pages\EditProduct;
use SmartTill\Core\Filament\Resources\Products\Pages\ListProducts;
use SmartTill\Core\Filament\Resources\Products\Pages\ViewProduct;
use SmartTill\Core\Filament\Resources\Products\RelationManagers\VariationsRelationManager;
use SmartTill\Core\Filament\Resources\Products\Schemas\ProductForm;
use SmartTill\Core\Filament\Resources\Products\Schemas\ProductInfolist;
use SmartTill\Core\Filament\Resources\Products\Tables\ProductsTable;
use SmartTill\Core\Models\Product;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::ArchiveBox;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Products');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Products');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Products');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Products');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Products');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Products');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Products');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Products');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'name', 'description', 'brand.name', 'category.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->reference ?: ($record->local_id ?: "#{$record->id}"),
            'Brand' => $record->brand?->name,
            'Category' => $record->category?->name,
            'Status' => $record->status,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::$model::query()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
