<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('type')
                            ->placeholder('Not set')
                            ->formatStateUsing(fn (?AccountTypes $state): ?string => $state?->label()),
                        TextEntry::make('role')
                            ->badge()
                            ->formatStateUsing(fn (Roles $state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (Status $state): string => $state->color())
                            ->formatStateUsing(fn (Status $state): string => $state->label()),
                        IconEntry::make('email_verified_at')
                            ->label('Email verified')
                            ->state(fn ($record): bool => $record->email_verified_at !== null)
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Audit')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
