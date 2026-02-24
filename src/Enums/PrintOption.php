<?php

namespace SmartTill\Core\Enums;

enum PrintOption: string
{
    case A4 = 'a4';
    case A5 = 'a5';
    case THERMAL_80MM = '80mm';
    case THERMAL_58MM = '58mm';

    public function getLabel(): string
    {
        return match ($this) {
            self::A4 => 'A4',
            self::A5 => 'A5',
            self::THERMAL_80MM => '80mm',
            self::THERMAL_58MM => '58mm',
        };
    }

    public function getWidth(): string
    {
        return match ($this) {
            self::A4 => '210',
            self::A5 => '148',
            self::THERMAL_80MM => '80',
            self::THERMAL_58MM => '58',
        };
    }

    public static function default(): self
    {
        return self::A4;
    }
}
