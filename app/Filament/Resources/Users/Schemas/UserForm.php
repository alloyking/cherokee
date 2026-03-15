<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_admin')
                    ->label('Admin')
                    ->default(false),
                DateTimePicker::make('email_verified_at')
                    ->label('Email verified at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->helperText('Leave blank when editing to keep the current password.')
                    ->maxLength(255),
            ]);
    }
}
