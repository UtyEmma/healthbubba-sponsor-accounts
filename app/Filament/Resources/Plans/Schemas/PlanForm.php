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
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        Section::make('Plan details')
                            ->description('Define the plan identity and the account audience that can subscribe.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                                Select::make('account_type')
                                    ->options(collect(AccountTypes::cases())
                                        ->mapWithKeys(fn (AccountTypes $type): array => [
                                            $type->value => $type->label(),
                                        ])
                                        ->all())
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                                Textarea::make('description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
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
                            ])
                            ->columns(1)
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        Section::make('Billing schedule')
                            ->description('Configure the recurring price, billing cadence, trial, and grace periods.')
                            ->schema([
                                TextInput::make('price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                                TextInput::make('billing_period')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 3, 'xl' => 4]),
                                Select::make('billing_interval')
                                    ->options(collect(Interval::cases())
                                        ->mapWithKeys(fn (Interval $interval): array => [
                                            $interval->value => str($interval->value)->headline()->toString(),
                                        ])
                                        ->all())
                                    ->default(Interval::Month->value)
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 3, 'xl' => 4]),
                                TextInput::make('trial_days')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                                TextInput::make('grace_days')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
