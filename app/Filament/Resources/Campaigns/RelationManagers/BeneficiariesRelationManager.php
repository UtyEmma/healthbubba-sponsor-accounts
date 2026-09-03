<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\WorkspaceBeneficiary;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class BeneficiariesRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiaries';

    protected static ?string $title = 'Campaign beneficiaries';

    protected static ?string $recordTitleAttribute = 'email';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Beneficiary')
                    ->formatStateUsing(static fn (WorkspaceBeneficiary $record): string => trim("{$record->first_name} {$record->last_name}"))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('community')
                    ->placeholder('Not provided')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(static fn (WorkspaceBeneficiarySource $state): string => Str::headline($state->value)),
                TextColumn::make('status')
                    ->badge()
                    ->color(static fn (WorkspaceBeneficiaryStatus $state): string => match ($state) {
                        WorkspaceBeneficiaryStatus::Active => 'success',
                        WorkspaceBeneficiaryStatus::Pending => 'warning',
                        WorkspaceBeneficiaryStatus::Suspended => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(static fn (WorkspaceBeneficiaryStatus $state): string => Str::headline($state->value)),
                TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
