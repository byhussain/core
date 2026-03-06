<?php

namespace SmartTill\Core\Filament\Resources\Suppliers;

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
use SmartTill\Core\Filament\Resources\Suppliers\Pages\CreateSupplier;
use SmartTill\Core\Filament\Resources\Suppliers\Pages\EditSupplier;
use SmartTill\Core\Filament\Resources\Suppliers\Pages\ListSuppliers;
use SmartTill\Core\Filament\Resources\Suppliers\Pages\ViewSupplier;
use SmartTill\Core\Filament\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use SmartTill\Core\Filament\Resources\Suppliers\RelationManagers\TransactionsRelationManager;
use SmartTill\Core\Filament\Resources\Suppliers\Schemas\SupplierForm;
use SmartTill\Core\Filament\Resources\Suppliers\Schemas\SupplierInfolist;
use SmartTill\Core\Filament\Resources\Suppliers\Tables\SuppliersTable;
use SmartTill\Core\Models\Supplier;
use UnitEnum;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::Truck;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Suppliers');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Suppliers');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Suppliers');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Suppliers');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Suppliers');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Suppliers');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Suppliers');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Suppliers');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Reference' => $record->reference ?: ($record->local_id ?: "#{$record->id}"),
            'Phone' => $record->phone,
            'Email' => $record->email,
            'Status' => $record->status,
        ];
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
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
            PurchaseOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
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
