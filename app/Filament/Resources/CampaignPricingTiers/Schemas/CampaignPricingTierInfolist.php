<?php

namespace App\Filament\Resources\CampaignPricingTiers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CampaignPricingTierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign pricing tier')
                ->columns(2)
                ->schema([
                    TextEntry::make('minimum_beneficiaries')->label('Minimum beneficiaries'),
                    TextEntry::make('maximum_beneficiaries')->label('Maximum beneficiaries')->placeholder('No maximum'),
                    TextEntry::make('beneficiary_unit_fee')->label('Fee per beneficiary')->money(fn ($record): string => $record->currency),
                    TextEntry::make('monthly_service_fee')
                        ->label('Monthly service fee')
                        ->money(fn ($record): string => $record->currency)
                        ->placeholder('Not charged'),
                    TextEntry::make('currency'),
                    IconEntry::make('is_active')->label('Available for new campaigns')->boolean(),
                    TextEntry::make('updatedBy.name')->label('Last updated by')->placeholder('System'),
                    TextEntry::make('updated_at')->dateTime()->placeholder('Never'),
                ]),
        ]);
    }
}
