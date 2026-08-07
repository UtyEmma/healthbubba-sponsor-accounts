<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->placeholder('Not set')
                    ->formatStateUsing(fn (?AccountTypes $state): ?string => $state?->label())
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (Roles $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (Status $state): string => $state->color())
                    ->formatStateUsing(fn (Status $state): string => $state->label())
                    ->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->state(fn ($record): bool => $record->email_verified_at !== null)
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(AccountTypes::cases())
                        ->mapWithKeys(fn (AccountTypes $type): array => [
                            $type->value => $type->label(),
                        ])
                        ->all()),
                SelectFilter::make('role')
                    ->options(collect(Roles::cases())
                        ->mapWithKeys(fn (Roles $role): array => [
                            $role->value => str($role->value)->headline()->toString(),
                        ])
                        ->all()),
                SelectFilter::make('status')
                    ->options(collect(Status::cases())
                        ->mapWithKeys(fn (Status $status): array => [
                            $status->value => $status->label(),
                        ])
                        ->all()),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
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
