<?php

namespace SmartTill\Core\Filament\Resources\CashTransactions\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use SmartTill\Core\Filament\Resources\CashTransactions\CashTransactionResource;

class ListCashTransactions extends ListRecords
{
    protected static string $resource = CashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['user', 'store', 'collectedBy']);
    }
}
