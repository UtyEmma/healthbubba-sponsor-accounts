<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\AccountTypes;
use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
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
            Textarea::make('description')
                ->rows(3)
                ->maxLength(2000)
                ->columnSpanFull(),
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
                                    ->nullable(),
                                TextInput::make('estimated_beneficiaries')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),
                                Toggle::make('booth_required')
                                    ->label('Booth required')
                                    ->helperText('Indicates whether HealthBubba support should arrange a booth for this campaign.')
                                    ->default(false)
                                    ->required(),
                            ])
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        Section::make('Consultation fees')
                            ->description('Set the per-consultation fee for scheduled and instant consultations. These fees are charged when the workspace purchases consultation quotas.')
                            ->schema([
                                TextInput::make('gp_fee')
                                    ->label('Scheduled consultation fee (₦)')
                                    ->helperText('Fee per scheduled consultation in Naira.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(config('campaigns.default_gp_fee', 0)),
                                TextInput::make('specialist_fee')
                                    ->label('Instant consultation fee (₦)')
                                    ->helperText('Fee per instant consultation in Naira.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(config('campaigns.default_specialist_fee', 0)),
                            ])
                            ->columnSpanFull(),
                        Section::make('Healthcare budgets')
                            ->schema([
                                TextInput::make('medication_budget')->numeric()->prefix('₦'),
                                TextInput::make('laboratory_budget')->numeric()->prefix('₦'),
                            ])
                            ->columnSpanFull(),
                        Section::make('Coverage rule overrides')
                            ->description('Leave fields empty to inherit the institutional funding program defaults.')
                            ->schema([
                                Select::make('coverage_type_override')
                                    ->options(collect(InstitutionalCoverageType::cases())
                                        ->mapWithKeys(fn (InstitutionalCoverageType $type): array => [$type->value => $type->label()])
                                        ->all())
                                    ->nullable(),
                                TextInput::make('gp_limit_per_beneficiary_override')
                                    ->numeric()->integer()->minValue(1)->nullable(),
                                TextInput::make('specialist_limit_per_beneficiary_override')
                                    ->numeric()->integer()->minValue(1)->nullable(),
                                TextInput::make('daily_consultation_limit_override')
                                    ->numeric()->integer()->minValue(1)->nullable(),
                                Select::make('coverage_expiry_override')
                                    ->options([InstitutionalCoverageExpiry::Annual->value => InstitutionalCoverageExpiry::Annual->label()])
                                    ->nullable(),
                                Select::make('payment_preference_override')
                                    ->options(collect(InstitutionalPaymentPreference::cases())
                                        ->mapWithKeys(fn (InstitutionalPaymentPreference $preference): array => [$preference->value => $preference->label()])
                                        ->all())
                                    ->nullable(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),
                        Section::make('Booth deployment')
                            ->schema([
                                TextInput::make('booth_count')->numeric()->integer()->minValue(1),
                                DatePicker::make('booth_preferred_deployment_date'),
                                TextInput::make('booth_site'),
                                TextInput::make('booth_contact_name'),
                                TextInput::make('booth_contact_phone'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
