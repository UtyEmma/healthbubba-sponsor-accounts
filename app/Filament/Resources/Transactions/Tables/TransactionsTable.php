<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Transaction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionTypes $state): string => str($state->name)->headline()->toString())
                    ->sortable(),
                TextColumn::make('flow')
                    ->badge()
                    ->color(fn (TransactionFlow $state): string => match ($state) {
                        TransactionFlow::CREDIT => 'success',
                        TransactionFlow::DEBIT => 'danger',
                    })
                    ->formatStateUsing(fn (TransactionFlow $state): string => str($state->name)->headline()->toString())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::COMPLETED => 'success',
                        TransactionStatus::PENDING => 'warning',
                        TransactionStatus::FAILED => 'danger',
                    })
                    ->formatStateUsing(fn (TransactionStatus $state): string => str($state->name)->headline()->toString())
                    ->sortable(),
                TextColumn::make('owner_label')
                    ->label('Owner')
                    ->state(fn (Transaction $record): string => self::morphLabel($record->owner)),
                TextColumn::make('transactable_label')
                    ->label('Transactable')
                    ->state(fn (Transaction $record): string => self::morphLabel($record->transactable))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(TransactionTypes::cases())
                        ->mapWithKeys(fn (TransactionTypes $type): array => [
                            $type->name => str($type->name)->headline()->toString(),
                        ])
                        ->all()),
                SelectFilter::make('flow')
                    ->options(collect(TransactionFlow::cases())
                        ->mapWithKeys(fn (TransactionFlow $flow): array => [
                            $flow->name => str($flow->name)->headline()->toString(),
                        ])
                        ->all()),
                SelectFilter::make('status')
                    ->options(collect(TransactionStatus::cases())
                        ->mapWithKeys(fn (TransactionStatus $status): array => [
                            $status->name => str($status->name)->headline()->toString(),
                        ])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    private static function morphLabel(?Model $model): string
    {
        if ($model === null) {
            return 'Unavailable';
        }

        $label = $model->getAttribute('name')
            ?? $model->getAttribute('reference')
            ?? '#'.$model->getKey();

        return class_basename($model).' · '.$label;
    }
}
