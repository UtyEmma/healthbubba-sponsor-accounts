<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'notifications';

    protected static ?string $title = 'Recent activity';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('data.title')->label('Activity'),
                TextColumn::make('data.actor.name')->label('Actor')->placeholder('HealthBubba System'),
                TextColumn::make('data.description')->label('Details')->wrap()->placeholder('—'),
                TextColumn::make('data.category')->badge(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
