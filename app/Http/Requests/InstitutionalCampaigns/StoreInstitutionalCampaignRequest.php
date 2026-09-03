<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\DTOs\Campaigns\CampaignBoothData;
use App\DTOs\Campaigns\CampaignDetailsData;
use App\DTOs\Campaigns\CampaignEnrollmentData;
use App\DTOs\Campaigns\CampaignHealthcareAllocationData;
use App\DTOs\Campaigns\CreateCampaignData;
use App\Enums\CampaignEnrollmentMethod;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\User;
use App\ValueObjects\NigeriaPhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInstitutionalCampaignRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $manageOnly = true;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'locations' => ['required', 'string', 'max:500'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'enrollment_method' => ['required', Rule::enum(CampaignEnrollmentMethod::class)],
            'estimated_beneficiaries' => ['required', 'integer', 'min:1', 'max:1000000'],
            'gp_units' => ['required', 'integer', 'min:0', 'max:10000000'],
            'specialist_units' => ['required', 'integer', 'min:0', 'max:10000000'],
            'medication_budget' => ['required', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'laboratory_budget' => ['required', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'booth_required' => ['required', 'boolean'],
            'booth_count' => ['nullable', Rule::requiredIf($this->boolean('booth_required')), 'integer', 'min:1', 'max:100'],
            'booth_preferred_deployment_date' => [
                'nullable',
                Rule::requiredIf($this->boolean('booth_required')),
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:end_date',
            ],
            'booth_site' => ['nullable', Rule::requiredIf($this->boolean('booth_required')), 'string', 'max:255'],
            'booth_contact_name' => ['nullable', Rule::requiredIf($this->boolean('booth_required')), 'string', 'max:255'],
            'booth_contact_phone' => ['nullable', Rule::requiredIf($this->boolean('booth_required')), 'regex:/^\+234\d{10}$/'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasAllocation = $this->integer('gp_units') > 0
                    || $this->integer('specialist_units') > 0
                    || (float) $this->input('medication_budget', 0) > 0
                    || (float) $this->input('laboratory_budget', 0) > 0;

                if (! $hasAllocation) {
                    $validator->errors()->add('allocation', 'Add at least one healthcare benefit allocation.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $boothPhone = $this->string('booth_contact_phone')->toString();

        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'description' => $this->string('description')->squish()->toString(),
            'locations' => collect(explode(',', $this->string('locations')->toString()))
                ->map(static fn (string $location): string => trim($location))
                ->filter()
                ->implode(', '),
            'medication_budget' => str_replace(',', '', $this->string('medication_budget')->toString()),
            'laboratory_budget' => str_replace(',', '', $this->string('laboratory_budget')->toString()),
            'booth_site' => $this->nullableSquishedString('booth_site'),
            'booth_contact_name' => $this->nullableSquishedString('booth_contact_name'),
            'booth_contact_phone' => $boothPhone === '' ? null : NigeriaPhoneNumber::normalize($boothPhone),
        ]);
    }

    public function campaignData(): CreateCampaignData
    {
        $user = $this->user();

        abort_unless($user instanceof User, 403);

        $boothRequired = $this->boolean('booth_required');

        return new CreateCampaignData(
            workspace: $this->workspace(),
            user: $user,
            details: new CampaignDetailsData(
                name: (string) $this->validated('name'),
                description: (string) $this->validated('description'),
                locations: (string) $this->validated('locations'),
                startDate: (string) $this->validated('start_date'),
                endDate: (string) $this->validated('end_date'),
            ),
            enrollment: new CampaignEnrollmentData(
                method: CampaignEnrollmentMethod::from((string) $this->validated('enrollment_method')),
                estimatedBeneficiaries: (int) $this->validated('estimated_beneficiaries'),
            ),
            healthcare: new CampaignHealthcareAllocationData(
                gpUnits: (int) $this->validated('gp_units'),
                specialistUnits: (int) $this->validated('specialist_units'),
                medicationBudget: (string) $this->validated('medication_budget'),
                laboratoryBudget: (string) $this->validated('laboratory_budget'),
            ),
            booth: new CampaignBoothData(
                required: $boothRequired,
                count: $boothRequired ? (int) $this->validated('booth_count') : null,
                preferredDeploymentDate: $boothRequired ? (string) $this->validated('booth_preferred_deployment_date') : null,
                site: $boothRequired ? (string) $this->validated('booth_site') : null,
                contactName: $boothRequired ? (string) $this->validated('booth_contact_name') : null,
                contactPhone: $boothRequired ? (string) $this->validated('booth_contact_phone') : null,
            ),
        );
    }

    private function nullableSquishedString(string $key): ?string
    {
        $value = $this->string($key)->squish()->toString();

        return $value === '' ? null : $value;
    }
}
