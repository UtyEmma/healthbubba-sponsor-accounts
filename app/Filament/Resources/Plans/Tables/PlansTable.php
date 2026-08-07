<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Enums\AccountTypes;
use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Revoltify\Subscriptionify\Enums\Interval;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('account_type')
                    ->label('Account type')
                    ->badge()
                    ->formatStateUsing(fn (AccountTypes $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('price')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('features_count')
                    ->counts('features')
                    ->label('Features')
                    ->sortable(),
                TextColumn::make('billing_interval')
                    ->label('Billing')
                    ->formatStateUsing(fn (Interval $state, Plan $record): string => $record->billing_period.' '.str($state->value)->plural($record->billing_period)->headline()->toString()),
                TextColumn::make('trial_days')
                    ->suffix(' days')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_free')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account type')
                    ->options(collect(AccountTypes::cases())
                        ->mapWithKeys(fn (AccountTypes $type): array => [
                            $type->value => $type->label(),
                        ])
                        ->all()),
                SelectFilter::make('billing_interval')
                    ->options(collect(Interval::cases())
                        ->mapWithKeys(fn (Interval $interval): array => [
                            $interval->value => str($interval->value)->headline()->toString(),
                        ])
                        ->all()),
                TernaryFilter::make('is_free'),
                TernaryFilter::make('is_active'),
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
