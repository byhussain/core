<?php

namespace SmartTill\Core\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PaymentMethod: string implements HasColor, HasLabel
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case Online = 'online';
    case Token = 'token';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque => 'Cheque',
            self::Online => 'Online',
            self::Token => 'Token',
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
