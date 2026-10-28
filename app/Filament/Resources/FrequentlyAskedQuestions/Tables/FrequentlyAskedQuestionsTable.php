<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Tables;

use App\Enums\AccountTypes;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class FrequentlyAskedQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('account_type')
                    ->label('Account type')
                    ->badge()
                    ->formatStateUsing(fn (AccountTypes $state): string => $state->label())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account type')
                    ->options(AccountTypes::options()),
                TernaryFilter::make('is_active')
                    ->label('Visible on billing page'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
