<?php

namespace SmartTill\Core\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use SmartTill\Core\Filament\Resources\Helpers\ResourceCanAccessHelper;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $relatedResource = VariationResource::class;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ResourceCanAccessHelper::check('View Variations');
    }

    public function table(Table $table): Table
    {
        return $table;
    }
}
