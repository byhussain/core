<?php

namespace SmartTill\Core\Filament\Resources\Suppliers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use SmartTill\Core\Filament\Resources\Suppliers\SupplierResource;
use SmartTill\Core\Filament\Resources\Suppliers\Widgets\SupplierPaymentStats;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplierPaymentStats::class,
        ];
    }
}
