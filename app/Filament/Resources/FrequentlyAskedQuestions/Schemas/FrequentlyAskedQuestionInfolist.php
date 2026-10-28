<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Schemas;

use App\Enums\AccountTypes;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class FrequentlyAskedQuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Frequently asked question')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('account_type')
                            ->label('Account type')
                            ->badge()
                            ->formatStateUsing(fn (AccountTypes $state): string => $state->label()),
                        IconEntry::make('is_active')
                            ->label('Visible on billing page')
                            ->boolean(),
                        TextEntry::make('question')
                            ->columnSpanFull(),
                        TextEntry::make('answer')
                            ->columnSpanFull(),
                        TextEntry::make('sort_order')
                            ->label('Display order'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('Never'),
                    ]),
            ]);
    }
}
