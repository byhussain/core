<?php

namespace SmartTill\Core\Filament\Resources\Variations\Pages;

use Filament\Resources\Pages\ListRecords;
use SmartTill\Core\Filament\Resources\Variations\VariationResource;

class ListVariations extends ListRecords
{
    protected static string $resource = VariationResource::class;
}
