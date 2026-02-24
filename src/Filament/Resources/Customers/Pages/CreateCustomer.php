<?php

namespace SmartTill\Core\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Customers\CustomerResource;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
