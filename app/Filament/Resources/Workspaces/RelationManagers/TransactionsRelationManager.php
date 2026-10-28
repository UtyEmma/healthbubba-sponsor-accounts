<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('reference')->copyable()->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('flow')->badge(),
                TextColumn::make('amount')->money(fn ($record): string => $record->currency),
                TextColumn::make('recordedBy.name')->label('Recorded by')->placeholder('System/customer'),
                TextColumn::make('status')->badge(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
