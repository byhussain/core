<?php

namespace SmartTill\Core\Filament\Resources\Sales;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Enums\SaleStatus;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Sales\Pages\CreateSale;
use SmartTill\Core\Filament\Resources\Sales\Pages\EditSale;
use SmartTill\Core\Filament\Resources\Sales\Pages\ListSales;
use SmartTill\Core\Filament\Resources\Sales\Pages\ViewSale;
use SmartTill\Core\Filament\Resources\Sales\RelationManagers\VariationsRelationManager;
use SmartTill\Core\Filament\Resources\Sales\Schemas\SaleForm;
use SmartTill\Core\Filament\Resources\Sales\Schemas\SaleInfolist;
use SmartTill\Core\Filament\Resources\Sales\Tables\SalesTable;
use SmartTill\Core\Models\Sale;
use UnitEnum;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::ShoppingCart;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Transactions';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Sales');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Sales');
    }

    public static function canView(Model $record): bool
    {
        return ResourceCanAccessHelper::check('View Sales');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Sales');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'customer.name', 'customer.phone', 'header_note', 'footer_note', 'note'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return 'Sale #'.($record->reference ?: ($record->local_id ?: $record->id));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Customer' => $record->customer?->name ?? 'Guest',
            'Total' => $record->total,
            'Payment' => $record->payment_status?->value ?? null,
            'Status' => $record->status?->value ?? null,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::$model::query()->whereDate('created_at', today())->count();
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function canEdit(Model $record): bool
    {
        return ResourceCanAccessHelper::check('Edit Sales');
    }

    public static function canDelete(Model $record): bool
    {
        return ResourceCanAccessHelper::check('Delete Sales')
            && $record->status === SaleStatus::Pending;
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
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view' => ViewSale::route('/{record}'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }
}
