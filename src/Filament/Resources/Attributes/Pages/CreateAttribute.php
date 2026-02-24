<?php

namespace SmartTill\Core\Filament\Resources\Attributes\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Attributes\AttributeResource;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
