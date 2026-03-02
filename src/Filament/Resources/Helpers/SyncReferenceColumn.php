<?php

namespace SmartTill\Core\Filament\Resources\Helpers;

use Filament\Tables\Columns\TextColumn;

class SyncReferenceColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('server_id')
            ->label('Reference')
            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '—')
            ->description(fn ($record): ?string => filled($record->local_id ?? null) ? (string) $record->local_id : null)
            ->searchable(['server_id', 'local_id'])
            ->sortable();
    }
}

