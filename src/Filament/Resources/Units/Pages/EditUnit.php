<?php

namespace SmartTill\Core\Filament\Resources\Units\Pages;

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use SmartTill\Core\Filament\Resources\Units\UnitResource;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn ($record) => UnitResource::canDelete($record))
                ->authorize(fn ($record) => UnitResource::canDelete($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['store_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
