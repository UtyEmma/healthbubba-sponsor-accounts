<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\AccountTypes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Enums\Interval;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Plan details')
                                    ->description('Define the plan identity and the account audience that can subscribe.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        Select::make('account_type')
                                            ->options(collect(AccountTypes::cases())
                                                ->mapWithKeys(fn (AccountTypes $type): array => [
                                                    $type->value => $type->label(),
                                                ])
                                                ->all())
                                            ->required(),
                                        Textarea::make('description')
                                            ->rows(5)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Billing schedule')
                                    ->description('Configure the recurring price, billing cadence, trial, and grace periods.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix('₦')
                                            ->default(0)
                                            ->required(),
                                        TextInput::make('billing_period')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required(),
                                        Select::make('billing_interval')
                                            ->options(collect(Interval::cases())
                                                ->mapWithKeys(fn (Interval $interval): array => [
                                                    $interval->value => str($interval->value)->headline()->toString(),
                                                ])
                                                ->all())
                                            ->default(Interval::Month->value)
                                            ->required(),
                                        TextInput::make('trial_days')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(),
                                        TextInput::make('grace_days')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(),
                                    ]),
                            ]),
                            Section::make('Availability')
                                ->description('Control visibility and ordering across account-specific plan lists.')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->default(true),
                                    Toggle::make('is_free')
                                        ->default(false),
                                    TextInput::make('sort_order')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(),
                                ]),
                    ]),
            ]);
    }
}
