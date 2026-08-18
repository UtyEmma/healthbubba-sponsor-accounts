<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use App\Enums\AccountTypes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkspaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Workspace')
                            ->description('Update the sponsor-facing workspace identity and description.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Account configuration')
                            ->description('Account type is fixed after registration. Campaign onboarding may be adjusted by support.')
                            ->schema([
                                Select::make('type')
                                    ->options(AccountTypes::options())
                                    ->disabled()
                                    ->dehydrated(false),
                                DateTimePicker::make('onboarded_at')
                                    ->label('Campaign onboarding completed at')
                                    ->helperText('Clear this value only when the institutional owner must repeat campaign onboarding.')
                                    ->nullable(),
                            ])
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                    ]),
            ]);
    }
}
