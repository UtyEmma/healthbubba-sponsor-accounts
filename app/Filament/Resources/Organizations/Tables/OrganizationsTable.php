<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\AccountTypes;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/sponsor/logo.svg')),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (AccountTypes $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(AccountTypes::cases())
                        ->reject(fn (AccountTypes $type): bool => $type === AccountTypes::INDIVIDUAL)
                        ->mapWithKeys(fn (AccountTypes $type): array => [
                            $type->value => $type->label(),
                        ])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
