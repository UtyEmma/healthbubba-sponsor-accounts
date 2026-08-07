<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->schema([
                        TextEntry::make('reference')->copyable(),
                        TextEntry::make('amount')->numeric(decimalPlaces: 2),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (TransactionTypes $state): string => str($state->name)->headline()->toString()),
                        TextEntry::make('flow')
                            ->badge()
                            ->color(fn (TransactionFlow $state): string => match ($state) {
                                TransactionFlow::CREDIT => 'success',
                                TransactionFlow::DEBIT => 'danger',
                            })
                            ->formatStateUsing(fn (TransactionFlow $state): string => str($state->name)->headline()->toString()),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (TransactionStatus $state): string => match ($state) {
                                TransactionStatus::COMPLETED => 'success',
                                TransactionStatus::PENDING => 'warning',
                                TransactionStatus::FAILED => 'danger',
                            })
                            ->formatStateUsing(fn (TransactionStatus $state): string => str($state->name)->headline()->toString()),
                    ])
                    ->columns(2),
                Section::make('Parties')
                    ->schema([
                        TextEntry::make('owner_label')
                            ->label('Owner')
                            ->state(fn (Transaction $record): string => self::morphLabel($record->owner)),
                        TextEntry::make('transactable_label')
                            ->label('Transactable')
                            ->state(fn (Transaction $record): string => self::morphLabel($record->transactable)),
                    ])
                    ->columns(2),
                Section::make('Audit')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
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
