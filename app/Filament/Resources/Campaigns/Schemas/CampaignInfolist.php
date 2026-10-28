<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign')
                    ->schema([
                        TextEntry::make('workspace.name')
                            ->label('Workspace'),
                        TextEntry::make('name')
                            ->label('Campaign'),
                        TextEntry::make('slug'),
                        TextEntry::make('status')
                            ->formatStateUsing(fn ($record): string => $record->lifecycleStatus()->label()),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('beneficiaries_count')
                            ->label('Beneficiaries')
                            ->numeric(),
                        TextEntry::make('beneficiary_limit')
                            ->label('Beneficiary limit')
                            ->numeric(),
                        TextEntry::make('start_date')
                            ->label('Start date')
                            ->date()
                            ->placeholder('Not provided'),
                        TextEntry::make('end_date')
                            ->label('End date')
                            ->date()
                            ->placeholder('Not provided'),
                        TextEntry::make('target_audience')
                            ->placeholder('Not provided')
                            ->columnSpanFull(),
                        IconEntry::make('booth_required')
                            ->label('Booth required')
                            ->boolean(),
                        TextEntry::make('scheduled_consultation_fee')
                            ->label('Scheduled consultation fee')
                            ->prefix('₦')
                            ->placeholder('Not set'),
                        TextEntry::make('instant_consultation_fee')
                            ->label('Instant consultation fee')
                            ->prefix('₦')
                            ->placeholder('Not set'),
                        TextEntry::make('medication_budget')->prefix('₦'),
                        TextEntry::make('laboratory_budget')->prefix('₦'),
                        TextEntry::make('returned_amount')->prefix('₦'),
                    ])
                    ->columns(2),
                Section::make('Location')
                    ->schema([
                        TextEntry::make('location')->placeholder('Not provided'),
                        TextEntry::make('city')->placeholder('Not provided'),
                        TextEntry::make('state')->placeholder('Not provided'),
                        TextEntry::make('country')->placeholder('Not provided'),
                    ])
                    ->columns(2),
                Section::make('Campaign service pricing')
                    ->description('Pricing captured when this campaign was launched. Later tier changes do not alter these amounts.')
                    ->schema([
                        TextEntry::make('estimated_beneficiaries')
                            ->label('Estimated beneficiaries')
                            ->numeric(),
                        TextEntry::make('beneficiary_unit_fee')
                            ->label('Fee per beneficiary')
                            ->money(fn ($record): string => $record->currency)
                            ->placeholder('Legacy campaign'),
                        TextEntry::make('beneficiary_setup_fee')
                            ->label('One-time beneficiary setup')
                            ->money(fn ($record): string => $record->currency)
                            ->placeholder('Legacy campaign'),
                        TextEntry::make('monthly_service_fee')
                            ->label('Monthly campaign service fee')
                            ->money(fn ($record): string => $record->currency)
                            ->placeholder('Legacy campaign'),
                    ])
                    ->columns(4),
                Section::make('Monitoring')
                    ->schema([
                        TextEntry::make('allocated_consultation_value')
                            ->label('Consultation allocation')
                            ->money(fn ($record): string => $record->currency),
                        TextEntry::make('utilized_healthcare_value')
                            ->label('Healthcare utilized')
                            ->money(fn ($record): string => $record->currency),
                        TextEntry::make('returned_amount')
                            ->label('Returned to wallet')
                            ->money(fn ($record): string => $record->currency),
                        TextEntry::make('beneficiaries_count')->label('Beneficiaries')->numeric(),
                        TextEntry::make('consultations_count')->label('Consultations')->numeric(),
                        TextEntry::make('booths_count')->label('Booths')->numeric(),
                        TextEntry::make('booth_charges_total')
                            ->label('Booth charges')
                            ->money(fn ($record): string => $record->currency),
                        TextEntry::make('active_recurring_costs_count')
                            ->label('Active recurring costs')
                            ->numeric(),
                    ])
                    ->columns(4),
                Section::make('Audit')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
