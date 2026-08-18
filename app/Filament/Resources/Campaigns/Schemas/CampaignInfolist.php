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
                        TextEntry::make('beneficiaries_count')
                            ->label('Beneficiaries')
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
