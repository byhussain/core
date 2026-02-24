<?php

namespace SmartTill\Core\Filament\Resources\CashTransactions\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartTill\Core\Filament\Resources\CashTransactions\CashTransactionResource;

class CreateCashTransaction extends CreateRecord
{
    protected static string $resource = CashTransactionResource::class;
}
