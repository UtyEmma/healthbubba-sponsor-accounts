<?php

namespace App\Filament\Resources\Features\Schemas;

use App\Enums\Subscriptions\Features;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Enums\FeatureType;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        Section::make('Feature details')
                            ->description('Describe the capability shown on account plan comparisons.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('slug')
                                    ->options(Features::options())
                                    ->searchable()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Behaviour')
                            ->description('Choose how the entitlement is measured and ordered.')
                            ->schema([
                                Select::make('type')
                                    ->options(collect(FeatureType::cases())
                                        ->mapWithKeys(fn (FeatureType $type): array => [
                                            $type->value => str($type->value)->headline()->toString(),
                                        ])
                                        ->all())
                                    ->required(),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(1)
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                    ]),
            ]);
    }
}
