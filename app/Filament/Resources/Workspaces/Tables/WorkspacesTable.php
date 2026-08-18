<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Enums\AccountTypes;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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
                TernaryFilter::make('onboarded_at')
                    ->label('Campaign onboarding completed')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
