<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\AccountTypes;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class WorkspacesRelationManager extends RelationManager
{
    protected static string $relationship = 'workspaces';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('latestSubscription.plan'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (AccountTypes $state): string => $state->label()),
                TextColumn::make('pivot.role')->label('Membership role')->badge(),
                TextColumn::make('pivot.status')->label('Membership status')->badge(),
                TextColumn::make('latestSubscription.plan.name')->label('Current plan')->placeholder('No plan'),
                TextColumn::make('latestSubscription.status')->label('Subscription')->badge()->placeholder('No subscription'),
                TextColumn::make('pivot.accepted_at')->label('Accepted')->dateTime()->placeholder('Pending'),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
