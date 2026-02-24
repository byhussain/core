<?php

namespace SmartTill\Core\Filament\Resources\Payments\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use SmartTill\Core\Filament\Resources\Payments\PaymentResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['payable']);
    }
}
