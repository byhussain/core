<?php

namespace SmartTill\Core\Filament\Resources\Units\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Units\UnitResource;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()?->getKey();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
