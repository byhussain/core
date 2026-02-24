<?php

namespace SmartTill\Core\Filament\Resources\Attributes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use SmartTill\Core\Filament\Resources\Attributes\AttributeResource;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
