<?php

namespace SmartTill\Core\Filament\Resources\Categories;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SmartTill\Core\Filament\Resources\Categories\Pages\CreateCategory;
use SmartTill\Core\Filament\Resources\Categories\Pages\EditCategory;
use SmartTill\Core\Filament\Resources\Categories\Pages\ListCategories;
use SmartTill\Core\Filament\Resources\Categories\Pages\ViewCategory;
use SmartTill\Core\Filament\Resources\Categories\RelationManagers\ProductsRelationManager;
use SmartTill\Core\Filament\Resources\Categories\Schemas\CategoryForm;
use SmartTill\Core\Filament\Resources\Categories\Schemas\CategoryInfolist;
use SmartTill\Core\Filament\Resources\Categories\Tables\CategoriesTable;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\Category;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Squares2x2;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Categories');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Categories');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Categories');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Categories');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Categories');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Categories');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Categories');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Categories');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Description' => $record->description,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
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
