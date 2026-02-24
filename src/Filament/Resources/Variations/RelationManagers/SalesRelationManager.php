<?php

namespace SmartTill\Core\Filament\Resources\Variations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Sales\SaleResource;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $relatedResource = SaleResource::class;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Sales');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->sales()->count();
    }

    public function table(Table $table): Table
    {
        return $table;
    }
}
