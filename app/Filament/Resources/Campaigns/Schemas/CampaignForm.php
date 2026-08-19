<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\AccountTypes;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CampaignForm
{
    public static function configure(Schema $schema, bool $includeWorkspace = true): Schema
    {
        $workspaceField = Select::make('workspace_id')
            ->label('Institutional workspace')
            ->relationship(
                name: 'workspace',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query
                    ->where('type', AccountTypes::INSTITUTION->value),
            )
            ->searchable()
            ->preload()
            ->required()
            ->rules([
                Rule::exists('workspaces', 'id')
                    ->where('type', AccountTypes::INSTITUTION->value),
            ])
            ->disabled(fn (string $operation): bool => $operation === 'edit')
            ->dehydrated(fn (string $operation): bool => $operation === 'create');

        $campaignDetails = [];

        if ($includeWorkspace) {
            $campaignDetails[] = $workspaceField;
        }

        $campaignDetails = [
            ...$campaignDetails,
            TextInput::make('name')
                ->label('Campaign')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->helperText('Used in sponsor-facing campaign URLs. Leave blank to generate it from the campaign name.')
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('country')
                ->maxLength(255),
            TextInput::make('state')
                ->maxLength(255),
            TextInput::make('city')
                ->maxLength(255),
            TextInput::make('location')
                ->label('Campaign location')
                ->maxLength(255),
            DatePicker::make('start_date')
                ->label('Start date')
                ->required(),
            DatePicker::make('end_date')
                ->label('End date')
                ->afterOrEqual('start_date')
                ->required(),
            Textarea::make('target_audience')
                ->label('Target audience')
                ->rows(3)
                ->maxLength(255)
                ->columnSpanFull(),
        ];

        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Campaign details')
                            ->description('Set up the campaign and associate it with an institutional workspace.')
                            ->schema($campaignDetails)
                            ->columns(2)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Administration')
                            ->description('Campaigns can be updated as support requirements change.')
                            ->schema([
                                TextInput::make('beneficiary_limit')
                                    ->label('Beneficiary limit')
                                    ->helperText('Maximum active, suspended, or pending beneficiaries for this campaign.')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(100000)
                                    ->default(100)
                                    ->required(),
                                Toggle::make('booth_required')
                                    ->label('Booth required')
                                    ->helperText('Indicates whether HealthBubba support should arrange a booth for this campaign.')
                                    ->default(false)
                                    ->required(),
                            ])
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        Section::make('Consultation fees')
                            ->description('Set the per-consultation fee for GP and specialist consultations. These fees are charged when the workspace purchases consultation quotas.')
                            ->schema([
                                TextInput::make('gp_fee')
                                    ->label('GP consultation fee (₦)')
                                    ->helperText('Fee per GP consultation in Naira.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(config('campaigns.default_gp_fee', 0)),
                                TextInput::make('specialist_fee')
                                    ->label('Specialist consultation fee (₦)')
                                    ->helperText('Fee per specialist consultation in Naira.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(config('campaigns.default_specialist_fee', 0)),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
