<?php

namespace SmartTill\Core\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SmartTill\Core\Enums\CustomerStatus;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('phone')
                            ->tel()
                            ->scopedUnique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('email')
                            ->email(),
                        TextInput::make('address'),
                        Select::make('status')
                            ->options(CustomerStatus::class)
                            ->default(CustomerStatus::default())
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Tax Information')
                    ->description('Required for FBR compliance')
                    ->schema([
                        TextInput::make('ntn')
                            ->label('NTN (National Tax Number)')
                            ->maxLength(9)
                            ->helperText('Format: 1234567-8')
                            ->placeholder('1234567-8'),
                        TextInput::make('cnic')
                            ->label('CNIC (Computerized National Identity Card)')
                            ->maxLength(13)
                            ->helperText('Format: 12345-1234567-8')
                            ->placeholder('12345-1234567-8'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
