<?php

namespace SmartTill\Core\Filament\Resources\Suppliers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\PurchaseOrders\PurchaseOrderResource;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $relatedResource = PurchaseOrderResource::class;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Purchase Orders');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->purchaseOrders()->count();
    }

    public function table(Table $table): Table
    {
        return $table;
    }
}
