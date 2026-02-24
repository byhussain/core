<?php

namespace SmartTill\Core\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum PurchaseOrderStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Open = 'open';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Open => 'info',
            self::Closed => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Open => Heroicon::ArrowPath,
            self::Closed => Heroicon::CheckBadge,
            self::Rejected => Heroicon::NoSymbol,
            self::Cancelled => Heroicon::XCircle,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => 'Purchase order request awaiting review or approval.',
            self::Open => 'Purchase order is approved and currently in progress.',
            self::Closed => 'Purchase order has been completed and closed.',
            self::Rejected => 'Purchase order request was rejected.',
            self::Cancelled => 'Purchase order was cancelled after receiving.',
        };
    }

    public static function default(): self
    {
        return self::Pending;
    }
}
