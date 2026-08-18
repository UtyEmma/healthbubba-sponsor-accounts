<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use App\Enums\AccountTypes;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkspaceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workspace')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (AccountTypes $state): string => $state->label()),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
                        TextEntry::make('onboarded_at')
                            ->label('Campaign onboarding completed')
                            ->dateTime()
                            ->placeholder('Not completed'),
                    ])
                    ->columns(2),
                Section::make('Workspace activity')
                    ->schema([
                        TextEntry::make('members_count')->label('Team members')->numeric(),
                        TextEntry::make('campaigns_count')->label('Campaigns')->numeric(),
                        TextEntry::make('latestCampaign.name')
                            ->label('Latest campaign')
                            ->placeholder('No campaign'),
                    ])
                    ->columns(3),
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
