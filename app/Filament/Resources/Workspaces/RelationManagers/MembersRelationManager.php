<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Workspace members';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('accepted_at')->dateTime()->placeholder('Pending'),
                TextColumn::make('last_selected_at')->dateTime()->placeholder('Never'),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
