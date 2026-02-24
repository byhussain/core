<?php

namespace SmartTill\Core\Filament\Resources\Categories\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use SmartTill\Core\Filament\Resources\Categories\CategoryResource;
use SmartTill\Core\Traits\ResourceHasRedirectUrl;

class EditCategory extends EditRecord
{
    use ResourceHasRedirectUrl;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
