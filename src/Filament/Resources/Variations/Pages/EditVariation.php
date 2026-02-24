<?php

namespace SmartTill\Core\Filament\Resources\Variations\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;
use SmartTill\Core\Models\Image;

class EditVariation extends EditRecord
{
    protected static string $resource = VariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
}
