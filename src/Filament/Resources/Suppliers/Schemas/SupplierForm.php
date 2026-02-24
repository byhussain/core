<?php

namespace SmartTill\Core\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use SmartTill\Core\Enums\SupplierStatus;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('address'),
                Select::make('status')
                    ->options(SupplierStatus::class)
                    ->default(SupplierStatus::default())
                    ->required(),
            ]);
    }
}
