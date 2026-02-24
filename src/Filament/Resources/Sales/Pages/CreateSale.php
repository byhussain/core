<?php

namespace SmartTill\Core\Filament\Resources\Sales\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use SmartTill\Core\Filament\Resources\Sales\SaleResource;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return self::$resource::getUrl('create');
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
