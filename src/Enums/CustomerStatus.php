<?php

namespace SmartTill\Core\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CustomerStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Defaulter = 'defaulter';
    case Blocked = 'blocked';

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Defaulter => 'danger',
            self::Blocked => 'danger',
            default => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Active => HeroIcon::CheckBadge,
            default => Heroicon::NoSymbol,
        };
    }

    public static function default(): self
    {
        return self::Active;
    }
}
