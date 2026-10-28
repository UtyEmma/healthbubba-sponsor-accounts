<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Schemas;

use App\Enums\AccountTypes;
use App\Models\FrequentlyAskedQuestion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class FrequentlyAskedQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Frequently asked question')
                            ->description('Write the question and answer displayed to sponsors on the billing page.')
                            ->columnSpan(2)
                            ->schema([
                                TextInput::make('question')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                Textarea::make('answer')
                                    ->required()
                                    ->rows(8)
                                    ->maxLength(5000)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Audience and visibility')
                            ->description('Choose which sponsor account type sees this answer and control its position.')
                            ->schema([
                                Select::make('account_type')
                                    ->label('Account type')
                                    ->options(AccountTypes::options())
                                    ->required()
                                    ->native(false),
                                Toggle::make('is_active')
                                    ->label('Visible on billing page')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Display order')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(fn (): int => ((int) FrequentlyAskedQuestion::query()->max('sort_order')) + 1)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
