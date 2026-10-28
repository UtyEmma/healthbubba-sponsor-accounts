<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Actions\Campaigns\CreateCampaignAction;
use App\DTOs\Campaigns\CampaignBoothData;
use App\DTOs\Campaigns\CampaignDetailsData;
use App\DTOs\Campaigns\CampaignEnrollmentData;
use App\DTOs\Campaigns\CampaignHealthcareAllocationData;
use App\DTOs\Campaigns\CreateCampaignData;
use App\Enums\AccountTypes;
use App\Enums\CampaignEnrollmentMethod;
use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\User;
use App\Models\Workspace;
use App\ValueObjects\NigeriaPhoneNumber;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class CreateCampaign extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @return list<Step> */
    protected function getSteps(): array
    {
        return [
            Step::make('Campaign')
                ->description('Select the institutional sponsor and define the campaign term.')
                ->columns(2)
                ->schema([
                    Select::make('workspace_id')
                        ->label('Institutional workspace')
                        ->options(fn (): array => Workspace::query()
                            ->where('type', AccountTypes::INSTITUTION)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->default(fn (): ?int => request()->integer('workspace') ?: null)
                        ->searchable()
                        ->required()
                        ->rules([Rule::exists('workspaces', 'id')->where('type', AccountTypes::INSTITUTION->value)]),
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('description')->maxLength(2000)->columnSpanFull(),
                    TextInput::make('locations')
                        ->label('Communities or locations')
                        ->helperText('Separate multiple locations with commas.')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    DatePicker::make('start_date')->required()->minDate(today()),
                    DatePicker::make('end_date')->required()->afterOrEqual('start_date'),
                ]),
            Step::make('Enrollment')
                ->description('Configure the expected beneficiary roster.')
                ->columns(2)
                ->schema([
                    Select::make('enrollment_method')
                        ->options([
                            CampaignEnrollmentMethod::Manual->value => 'Manual enrollment',
                            CampaignEnrollmentMethod::Upload->value => 'Bulk upload',
                        ])
                        ->default(CampaignEnrollmentMethod::Manual->value)
                        ->required(),
                    TextInput::make('estimated_beneficiaries')
                        ->numeric()->integer()->minValue(1)->maxValue(1000000)->required(),
                ]),
            Step::make('Allocation')
                ->description('Reserve sponsor-funded healthcare value from the workspace wallet.')
                ->columns(2)
                ->schema([
                    TextInput::make('scheduled_units')
                        ->label('Scheduled consultation units')
                        ->numeric()->integer()->minValue(0)->default(0)->required(),
                    TextInput::make('instant_units')
                        ->label('Instant consultation units')
                        ->numeric()->integer()->minValue(0)->default(0)->required(),
                    TextInput::make('medication_budget')
                        ->numeric()->minValue(0)->default(0)->prefix('₦')->required(),
                    TextInput::make('laboratory_budget')
                        ->numeric()->minValue(0)->default(0)->prefix('₦')->required(),
                ]),
            Step::make('Coverage rules')
                ->description('Leave overrides empty to use the institutional program defaults.')
                ->columns(2)
                ->schema([
                    Select::make('coverage_type_override')
                        ->options(collect(InstitutionalCoverageType::cases())->mapWithKeys(fn (InstitutionalCoverageType $type): array => [$type->value => $type->label()])->all()),
                    TextInput::make('scheduled_limit_override')->numeric()->integer()->minValue(1),
                    TextInput::make('instant_limit_override')->numeric()->integer()->minValue(1),
                    TextInput::make('daily_limit_override')->numeric()->integer()->minValue(1),
                    Select::make('coverage_expiry_override')
                        ->options([InstitutionalCoverageExpiry::Annual->value => InstitutionalCoverageExpiry::Annual->label()]),
                    Select::make('payment_preference_override')
                        ->options(collect(InstitutionalPaymentPreference::cases())->mapWithKeys(fn (InstitutionalPaymentPreference $preference): array => [$preference->value => $preference->label()])->all()),
                ]),
            Step::make('Booths')
                ->description('Optionally arrange HealthBubba booth deployment. Setup is charged from the sponsor-funded wallet.')
                ->columns(2)
                ->schema([
                    Toggle::make('booth_required')->default(false)->live()->columnSpanFull(),
                    TextInput::make('booth_count')
                        ->numeric()->integer()->minValue(1)->maxValue(100)
                        ->required(fn (Get $get): bool => (bool) $get('booth_required'))
                        ->visible(fn (Get $get): bool => (bool) $get('booth_required')),
                    DatePicker::make('booth_preferred_deployment_date')
                        ->required(fn (Get $get): bool => (bool) $get('booth_required'))
                        ->visible(fn (Get $get): bool => (bool) $get('booth_required')),
                    TextInput::make('booth_site')
                        ->required(fn (Get $get): bool => (bool) $get('booth_required'))
                        ->visible(fn (Get $get): bool => (bool) $get('booth_required')),
                    TextInput::make('booth_contact_name')
                        ->required(fn (Get $get): bool => (bool) $get('booth_required'))
                        ->visible(fn (Get $get): bool => (bool) $get('booth_required')),
                    TextInput::make('booth_contact_phone')
                        ->tel()
                        ->required(fn (Get $get): bool => (bool) $get('booth_required'))
                        ->visible(fn (Get $get): bool => (bool) $get('booth_required')),
                ]),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $administrator = Auth::user();
        abort_unless($administrator instanceof User && $administrator->isAdmin(), 403);
        $workspace = Workspace::query()
            ->whereKey((int) $data['workspace_id'])
            ->where('type', AccountTypes::INSTITUTION)
            ->firstOrFail();
        $boothRequired = (bool) ($data['booth_required'] ?? false);

        return app(CreateCampaignAction::class)->execute(new CreateCampaignData(
            workspace: $workspace,
            user: $administrator,
            details: new CampaignDetailsData(
                name: (string) $data['name'],
                description: filled($data['description'] ?? null) ? (string) $data['description'] : null,
                locations: collect(explode(',', (string) $data['locations']))->map(fn (string $location): string => trim($location))->filter()->implode(', '),
                startDate: (string) $data['start_date'],
                endDate: (string) $data['end_date'],
            ),
            enrollment: new CampaignEnrollmentData(
                method: CampaignEnrollmentMethod::from((string) $data['enrollment_method']),
                estimatedBeneficiaries: (int) $data['estimated_beneficiaries'],
            ),
            healthcare: new CampaignHealthcareAllocationData(
                scheduledUnits: (int) $data['scheduled_units'],
                instantUnits: (int) $data['instant_units'],
                medicationBudget: (string) $data['medication_budget'],
                laboratoryBudget: (string) $data['laboratory_budget'],
            ),
            booth: new CampaignBoothData(
                required: $boothRequired,
                count: $boothRequired ? (int) $data['booth_count'] : null,
                preferredDeploymentDate: $boothRequired ? (string) $data['booth_preferred_deployment_date'] : null,
                site: $boothRequired ? (string) $data['booth_site'] : null,
                contactName: $boothRequired ? (string) $data['booth_contact_name'] : null,
                contactPhone: $boothRequired ? NigeriaPhoneNumber::normalize((string) $data['booth_contact_phone']) : null,
            ),
            coverageTypeOverride: filled($data['coverage_type_override'] ?? null) ? InstitutionalCoverageType::from((string) $data['coverage_type_override']) : null,
            scheduledLimitOverride: filled($data['scheduled_limit_override'] ?? null) ? (int) $data['scheduled_limit_override'] : null,
            instantLimitOverride: filled($data['instant_limit_override'] ?? null) ? (int) $data['instant_limit_override'] : null,
            dailyLimitOverride: filled($data['daily_limit_override'] ?? null) ? (int) $data['daily_limit_override'] : null,
            coverageExpiryOverride: filled($data['coverage_expiry_override'] ?? null) ? InstitutionalCoverageExpiry::from((string) $data['coverage_expiry_override']) : null,
            paymentPreferenceOverride: filled($data['payment_preference_override'] ?? null) ? InstitutionalPaymentPreference::from((string) $data['payment_preference_override']) : null,
        ));
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Campaign launched';
    }
}
