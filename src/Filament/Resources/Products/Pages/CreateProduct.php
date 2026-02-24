<?php

namespace SmartTill\Core\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\Products\ProductResource;
use SmartTill\Core\Models\Image;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['image_paths']);

        return $data;
    }

    protected function afterCreate(): void
    {
        Image::syncFor($this->record, $this->data['image_paths'] ?? []);
    }
}
