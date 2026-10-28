<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\Subscriptions\SubscriptionFundingSource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subscribable')
                    ->label('Subscriber')
                    ->formatStateUsing(fn (?Model $state): string => $state?->getAttribute('name') ?? 'Unknown subscriber'),
                TextColumn::make('subscribable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->toggleable(),
                TextColumn::make('plan.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Trialing => 'info',
                        SubscriptionStatus::PastDue => 'warning',
                        SubscriptionStatus::Cancelled, SubscriptionStatus::Expired => 'danger',
                    })
                    ->formatStateUsing(fn (SubscriptionStatus $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('funding_source')
                    ->label('Funding')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionFundingSource $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned by')
                    ->placeholder('Customer/system'),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->placeholder('No end date')
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $status): array => [
                            $status->value => str($status->value)->headline()->toString(),
                        ])
                        ->all()),
                SelectFilter::make('funding_source')
                    ->options(collect(SubscriptionFundingSource::cases())
                        ->mapWithKeys(fn (SubscriptionFundingSource $source): array => [
                            $source->value => $source->label(),
                        ])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
