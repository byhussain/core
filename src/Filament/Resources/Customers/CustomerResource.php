<?php

namespace SmartTill\Core\Filament\Resources\Customers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SmartTill\Core\Filament\Resources\Customers\Pages\CreateCustomer;
use SmartTill\Core\Filament\Resources\Customers\Pages\EditCustomer;
use SmartTill\Core\Filament\Resources\Customers\Pages\ListCustomers;
use SmartTill\Core\Filament\Resources\Customers\Pages\ViewCustomer;
use SmartTill\Core\Filament\Resources\Customers\RelationManagers\SalesRelationManager;
use SmartTill\Core\Filament\Resources\Customers\RelationManagers\TransactionsRelationManager;
use SmartTill\Core\Filament\Resources\Customers\Schemas\CustomerForm;
use SmartTill\Core\Filament\Resources\Customers\Schemas\CustomerInfolist;
use SmartTill\Core\Filament\Resources\Customers\Tables\CustomersTable;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Models\Customer;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::UserGroup;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Transactions';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Customers');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Customers');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Customers');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Customers');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Customers');
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Customers');
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Customers');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Customers');
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

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
            SalesRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
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
