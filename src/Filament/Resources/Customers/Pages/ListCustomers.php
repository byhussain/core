<?php

namespace SmartTill\Core\Filament\Resources\Customers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use SmartTill\Core\Filament\Resources\Customers\CustomerResource;
use SmartTill\Core\Filament\Resources\Customers\Widgets\CustomerPaymentStats;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerPaymentStats::class,
        ];
    }
}
