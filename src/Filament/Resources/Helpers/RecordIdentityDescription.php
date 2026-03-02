<?php

namespace SmartTill\Core\Filament\Resources\Helpers;

class RecordIdentityDescription
{
    public static function make(mixed $record): ?string
    {
        $serverId = trim((string) ($record->server_id ?? ''));
        $localId = trim((string) ($record->local_id ?? ''));

        if ($serverId === '' && $localId === '') {
            return null;
        }

        return 'Server: '.($serverId !== '' ? $serverId : '—').' | Local: '.($localId !== '' ? $localId : '—');
    }
}

