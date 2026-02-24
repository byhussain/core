<?php

namespace SmartTill\Core\Filament\Resources\CashTransactions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use SmartTill\Core\Filament\Resources\CashTransactions\CashTransactionResource;

class EditCashTransaction extends EditRecord
{
    protected static string $resource = CashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
