<?php

namespace SmartTill\Core\Enums;

enum FbrEnvironment: string
{
    case SANDBOX = 'sandbox';
    case PRODUCTION = 'production';

    public function getLabel(): string
    {
        return match ($this) {
            self::SANDBOX => 'Sandbox',
            self::PRODUCTION => 'Production',
        };
    }

    public function getApiUrl(): string
    {
        return match ($this) {
            self::SANDBOX => 'https://esp.fbr.gov.pk:8244/FBR/v1/api/Live/PostData',
            self::PRODUCTION => 'https://gw.fbr.gov.pk/imsp/v1/api/Live/PostData',
        };
    }
}
