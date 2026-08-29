<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\DTOs\Campaigns\AllocateMoreToCampaignData;

final class AllocateMoreToCampaignRequest extends ManageInstitutionalCampaignRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'gp_units' => ['required', 'integer', 'min:0', 'max:1000000'],
            'specialist_units' => ['required', 'integer', 'min:0', 'max:1000000'],
            'medication_budget' => ['required', 'numeric', 'min:0', 'max:100000000000'],
            'laboratory_budget' => ['required', 'numeric', 'min:0', 'max:100000000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['medication_budget', 'laboratory_budget'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => str_replace([',', ' '], '', (string) $this->input($field))]);
            }
        }
    }

    public function dto(): AllocateMoreToCampaignData
    {
        return new AllocateMoreToCampaignData(
            workspace: $this->workspace(),
            user: $this->onboardingUser(),
            campaign: $this->campaign(),
            gpUnits: (int) $this->validated('gp_units'),
            specialistUnits: (int) $this->validated('specialist_units'),
            medicationBudget: (string) $this->validated('medication_budget'),
            laboratoryBudget: (string) $this->validated('laboratory_budget'),
        );
    }
}
