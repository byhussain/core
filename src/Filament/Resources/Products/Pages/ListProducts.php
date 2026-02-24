<?php

namespace SmartTill\Core\Filament\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use SmartTill\Core\Filament\Resources\Products\ProductResource;
use SmartTill\Core\Filament\Resources\Products\Widgets\ProductStats;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductStats::class,
        ];
    }
}
