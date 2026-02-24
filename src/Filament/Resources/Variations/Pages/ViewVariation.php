<?php

namespace SmartTill\Core\Filament\Resources\Variations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;

class ViewVariation extends ViewRecord
{
    protected static string $resource = VariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->color('warning'),
        ];
    }
}
