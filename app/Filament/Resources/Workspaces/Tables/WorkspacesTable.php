<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Enums\AccountTypes;
use App\Filament\Resources\Workspaces\Actions\WorkspaceAdminActions;
use App\Models\Plan;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (AccountTypes $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label('Members')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('campaigns_count')
                    ->label('Campaigns')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('latestSubscription.plan.name')
                    ->label('Current plan')
                    ->placeholder('No plan'),
                TextColumn::make('latestSubscription.status')
                    ->label('Billing status')
                    ->badge()
                    ->placeholder('No subscription'),
                TextColumn::make('wallet.balance')
                    ->label('Wallet balance')
                    ->money(fn ($record): string => $record->wallet->currency)
                    ->sortable(),
                TextColumn::make('latestCampaign.name')
                    ->label('Latest campaign')
                    ->placeholder('No campaign'),
                TextColumn::make('onboarded_at')
                    ->label('Onboarded')
                    ->dateTime()
                    ->placeholder('Pending')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(AccountTypes::options()),
                SelectFilter::make('plan_id')
                    ->label('Current plan')
                    ->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        $planId = $data['value'] ?? null;

                        return blank($planId)
                            ? $query
                            : $query->whereHas('latestSubscription', fn (Builder $subscription): Builder => $subscription->where('plan_id', $planId));
                    }),
                SelectFilter::make('subscription_status')
                    ->label('Billing status')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status): array => [
                        $status->value => str($status->value)->headline()->toString(),
                    ])->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        return blank($status)
                            ? $query
                            : $query->whereHas('latestSubscription', fn (Builder $subscription): Builder => $subscription->where('status', $status));
                    }),
                TernaryFilter::make('onboarded_at')
                    ->label('Campaign onboarding completed')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    WorkspaceAdminActions::recordFunding(),
                    WorkspaceAdminActions::assignSubscription(),
                ])->label('Billing actions'),
            ]);
    }
}
