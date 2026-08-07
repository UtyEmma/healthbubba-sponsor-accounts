<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        Section::make('Subscriber and plan')
                            ->description('Assign an account owner and an account-compatible plan.')
                            ->schema([
                                MorphToSelect::make('subscribable')
                                    ->types([
                                        MorphToSelect\Type::make(User::class)
                                            ->titleAttribute('name'),
                                        MorphToSelect\Type::make(Organization::class)
                                            ->titleAttribute('name'),
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('plan_id')
                                    ->relationship('plan', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Status')
                            ->description('The current lifecycle state shown to the subscriber.')
                            ->schema([
                                Select::make('status')
                                    ->options(collect(SubscriptionStatus::cases())
                                        ->mapWithKeys(fn (SubscriptionStatus $status): array => [
                                            $status->value => str($status->value)->headline()->toString(),
                                        ])
                                        ->all())
                                    ->default(SubscriptionStatus::Active->value)
                                    ->required(),
                            ])
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        Section::make('Subscription schedule')
                            ->description('Set the effective term and record trial, cancellation, and renewal events.')
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                                DateTimePicker::make('ends_at')
                                    ->after('starts_at')
                                    ->nullable()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                                DateTimePicker::make('trial_ends_at')
                                    ->after('starts_at')
                                    ->nullable()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                                DateTimePicker::make('cancelled_at')
                                    ->nullable()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                                DateTimePicker::make('renewed_at')
                                    ->nullable()
                                    ->columnSpan(['default' => 'full', 'md' => 6, 'xl' => 4]),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
