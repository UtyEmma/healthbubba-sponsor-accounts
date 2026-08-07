<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\AccountTypes;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization')
                    ->schema([
                        ImageEntry::make('logo')
                            ->disk('public')
                            ->circular()
                            ->defaultImageUrl(url('/images/sponsor/logo.svg')),
                        TextEntry::make('name'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (AccountTypes $state): string => $state->label()),
                        TextEntry::make('users_count')
                            ->label('Members')
                            ->counts('users'),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
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
