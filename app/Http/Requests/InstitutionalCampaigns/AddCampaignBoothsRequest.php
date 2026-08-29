<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\DTOs\Campaigns\AddCampaignBoothsData;
use App\ValueObjects\NigeriaPhoneNumber;

final class AddCampaignBoothsRequest extends ManageInstitutionalCampaignRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'preferred_deployment_date' => ['required', 'date', 'after_or_equal:today'],
            'site' => ['required', 'string', 'max:255'],
            'community' => ['required', 'string', 'max:255'],
            'expected_beneficiaries' => ['required', 'integer', 'min:1', 'max:1000000'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'regex:/^\+234\d{10}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'site' => $this->string('site')->squish()->toString(),
            'community' => $this->string('community')->squish()->toString(),
            'contact_name' => $this->string('contact_name')->squish()->toString(),
            'contact_phone' => NigeriaPhoneNumber::normalize($this->string('contact_phone')->toString()),
        ]);
    }

    public function dto(): AddCampaignBoothsData
    {
        return new AddCampaignBoothsData(
            workspace: $this->workspace(),
            user: $this->onboardingUser(),
            campaign: $this->campaign(),
            count: (int) $this->validated('count'),
            preferredDeploymentDate: (string) $this->validated('preferred_deployment_date'),
            site: (string) $this->validated('site'),
            community: (string) $this->validated('community'),
            expectedBeneficiaries: (int) $this->validated('expected_beneficiaries'),
            contactName: (string) $this->validated('contact_name'),
            contactPhone: (string) $this->validated('contact_phone'),
        );
    }
}
