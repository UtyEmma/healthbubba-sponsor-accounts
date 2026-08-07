<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription')
                    ->schema([
                        TextEntry::make('subscribable')
                            ->label('Subscriber')
                            ->formatStateUsing(fn (?Model $state): string => $state?->getAttribute('name') ?? 'Unknown subscriber'),
                        TextEntry::make('subscribable_type')
                            ->label('Subscriber type')
                            ->formatStateUsing(fn (string $state): string => class_basename($state)),
                        TextEntry::make('plan.name')->label('Plan'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (SubscriptionStatus $state): string => match ($state) {
                                SubscriptionStatus::Active => 'success',
                                SubscriptionStatus::Trialing => 'info',
                                SubscriptionStatus::PastDue => 'warning',
                                SubscriptionStatus::Cancelled, SubscriptionStatus::Expired => 'danger',
                            })
                            ->formatStateUsing(fn (SubscriptionStatus $state): string => str($state->value)->headline()->toString()),
                    ])
                    ->columns(2),
                Section::make('Schedule')
                    ->schema([
                        TextEntry::make('starts_at')->dateTime(),
                        TextEntry::make('ends_at')->dateTime()->placeholder('No end date'),
                        TextEntry::make('trial_ends_at')->dateTime()->placeholder('No trial'),
                        TextEntry::make('cancelled_at')->dateTime()->placeholder('Not cancelled'),
                        TextEntry::make('renewed_at')->dateTime()->placeholder('Not renewed'),
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
