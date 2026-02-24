<?php

namespace SmartTill\Core\Enums;

enum BatchIdentifier: string
{
    case Manual = 'M';
    case PurchaseOrder = 'PO';
    case ManualImport = 'MI';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::PurchaseOrder => 'Purchase Order',
            self::ManualImport => 'Manual Import',
        };
    }
}
