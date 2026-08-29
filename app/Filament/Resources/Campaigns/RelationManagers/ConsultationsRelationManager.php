<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Models\Consultations\Consultation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ConsultationsRelationManager extends RelationManager
{
    protected static string $relationship = 'consultations';

    protected static ?string $title = 'Campaign consultations';

    protected static ?string $recordTitleAttribute = 'public_id';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with('workspaceBeneficiary'))
            ->defaultSort('reserved_at', 'desc')
            ->columns([
                TextColumn::make('public_id')
                    ->label('Reference')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('workspaceBeneficiary.first_name')
                    ->label('Beneficiary')
                    ->formatStateUsing(static fn (Consultation $record): string => trim(
                        "{$record->workspaceBeneficiary->first_name} {$record->workspaceBeneficiary->last_name}",
                    ))
                    ->searchable(query: static fn (Builder $query, string $search): Builder => $query
                        ->whereHas('workspaceBeneficiary', static fn (Builder $beneficiaries): Builder => $beneficiaries
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))),
                TextColumn::make('consultation_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(static fn (ConsultationType $state): string => $state->label()),
                TextColumn::make('plan_name')
                    ->label('Plan')
                    ->placeholder('Not provided')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(static fn (ConsultationReservationStatus $state): string => match ($state) {
                        ConsultationReservationStatus::Confirmed => 'success',
                        ConsultationReservationStatus::Reserved => 'warning',
                        ConsultationReservationStatus::Cancelled => 'gray',
                    })
                    ->formatStateUsing(static fn (ConsultationReservationStatus $state): string => Str::headline($state->value)),
                TextColumn::make('reserved_at')
                    ->label('Reserved')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('confirmed_at')
                    ->label('Confirmed')
                    ->dateTime()
                    ->placeholder('Not confirmed')
                    ->toggleable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
