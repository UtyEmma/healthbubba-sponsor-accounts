<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\Subscriptions\SubscriptionFundingSource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('plan.name')->label('Plan'),
                TextColumn::make('status')->badge(),
                TextColumn::make('funding_source')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionFundingSource $state): string => $state->label()),
                IconColumn::make('complimentary_renews')->boolean(),
                TextColumn::make('starts_at')->dateTime(),
                TextColumn::make('ends_at')->dateTime()->placeholder('No end date'),
                TextColumn::make('assignedBy.name')->label('Assigned by')->placeholder('Customer'),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
