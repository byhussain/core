<?php

namespace SmartTill\Core\Filament\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use SmartTill\Core\Filament\Resources\Products\ProductResource;
use SmartTill\Core\Models\Image;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['image_paths'] = $this->record->images()->orderBy('sort_order')->pluck('path')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['image_paths']);

        return $data;
    }

    protected function afterSave(): void
    {
        Image::syncFor($this->record, $this->data['image_paths'] ?? []);
    }

    public function getRelationManagers(): array
    {
        return []; // Hide relations on edit page only
    }
}
