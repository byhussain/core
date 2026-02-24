<?php

namespace SmartTill\Core\Filament\Resources\Brands\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Brands\BrandResource;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
