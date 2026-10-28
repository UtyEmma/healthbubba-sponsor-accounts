<?php

namespace App\Filament\Resources\CampaignPricingTiers\Tables;

use App\Models\CampaignPricingTier;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CampaignPricingTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('minimum_beneficiaries')
                    ->label('Beneficiary range')
                    ->formatStateUsing(static fn (int $state, CampaignPricingTier $record): string => $record->maximum_beneficiaries === null
                        ? number_format($state).'+ beneficiaries'
                        : number_format($state).'–'.number_format($record->maximum_beneficiaries).' beneficiaries')
                    ->sortable(),
                TextColumn::make('beneficiary_unit_fee')
                    ->label('Fee per beneficiary')
                    ->money(fn (CampaignPricingTier $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('monthly_service_fee')
                    ->label('Monthly service fee')
                    ->money(fn (CampaignPricingTier $record): string => $record->currency)
                    ->placeholder('Not charged')
                    ->sortable(),
                TextColumn::make('currency')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Available for new campaigns'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
