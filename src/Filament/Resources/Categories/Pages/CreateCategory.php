<?php

namespace SmartTill\Core\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Categories\CategoryResource;
use SmartTill\Core\Traits\ResourceHasRedirectUrl;

class CreateCategory extends CreateRecord
{
    use ResourceHasRedirectUrl;

    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
