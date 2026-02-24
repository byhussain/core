<?php

namespace SmartTill\Core\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SaleStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Completed => Heroicon::CheckBadge,
            self::Cancelled => Heroicon::XCircle,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => 'Order is awaiting processing.',
            self::Completed => 'Order has been fulfilled.',
            self::Cancelled => 'Order has been cancelled.',
        };
    }

    public static function default(): self
    {
        return self::Completed;
    }
}
