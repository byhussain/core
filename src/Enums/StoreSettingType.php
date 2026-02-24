<?php

namespace SmartTill\Core\Enums;

enum StoreSettingType: string
{
    case String = 'string';
    case TextArea = 'text_area';
    case Number = 'number';
    case Dropdown = 'dropdown';
}
