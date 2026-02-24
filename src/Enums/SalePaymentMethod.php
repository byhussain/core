<?php

namespace SmartTill\Core\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SalePaymentMethod: string implements HasColor, HasLabel
{
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case Multiple = 'multiple';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::CreditCard => 'Credit Card',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque => 'Cheque',
            self::Multiple => 'Multiple',
        };
    }

    public function getColor(): string|array|null
    {
        return 'info';
    }

    public static function default(): self
    {
        return self::Cash;
    }
}
