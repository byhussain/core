<?php

namespace SmartTill\Core\Traits;

trait ResourceHasRedirectUrl
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
