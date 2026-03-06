<?php

namespace SmartTill\Core\Filament\Resources\PurchaseOrders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SmartTill\Core\Enums\PurchaseOrderStatus;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Pages\ClosePurchaseOrder;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use SmartTill\Core\Filament\Resources\PurchaseOrders\RelationManagers\VariationsRelationManager;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use SmartTill\Core\Filament\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use SmartTill\Core\Models\PurchaseOrder;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return Heroicon::ArchiveBoxArrowDown;
    }

    protected static string|UnitEnum|null $navigationGroup = 'Purchases';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return ResourceCanAccessHelper::check('View Purchase Orders');
    }

    public static function canViewAny(): bool
    {
        return ResourceCanAccessHelper::check('View Purchase Orders');
    }

    public static function canView($record): bool
    {
        return ResourceCanAccessHelper::check('View Purchase Orders');
    }

    public static function canCreate(): bool
    {
        return ResourceCanAccessHelper::check('Create Purchase Orders');
    }

    public static function canEdit($record): bool
    {
        return ResourceCanAccessHelper::check('Edit Purchase Orders')
            && $record->status !== PurchaseOrderStatus::Closed;
    }

    public static function canDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Delete Purchase Orders')
            && $record->status !== PurchaseOrderStatus::Closed;
    }

    public static function canRestore($record): bool
    {
        return ResourceCanAccessHelper::check('Restore Purchase Orders');
    }

    public static function canForceDelete($record): bool
    {
        return ResourceCanAccessHelper::check('Force Delete Purchase Orders');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'local_id', 'supplier.name', 'supplier.phone', 'note'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return 'PO #'.($record->reference ?: ($record->local_id ?: $record->id));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Supplier' => $record->supplier?->name,
            'Status' => $record->status?->value ?? null,
            'Total' => $record->total,
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
        return PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
            'close' => ClosePurchaseOrder::route('/{record}/close'),
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
