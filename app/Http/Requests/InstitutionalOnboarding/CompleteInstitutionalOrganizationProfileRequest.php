<?php

namespace App\Http\Requests\InstitutionalOnboarding;

use App\DTOs\InstitutionalOnboarding\InstitutionalCampaignOnboardingData;

final class CompleteInstitutionalOrganizationProfileRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $ownerOnly = true;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'campaign_name' => ['required', 'string', 'max:255'],
            'campaign_location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'booth_required' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $campaignLocation = $this->string('campaign_location')->squish()->toString();
        $targetAudience = $this->string('target_audience')->squish()->toString();

        $this->merge([
            'city' => $this->string('city')->squish()->toString(),
            'state' => $this->string('state')->squish()->toString(),
            'campaign_name' => $this->string('campaign_name')->squish()->toString(),
            'campaign_location' => $campaignLocation === '' ? null : $campaignLocation,
            'target_audience' => $targetAudience === '' ? null : $targetAudience,
        ]);
    }

    public function onboardingData(): InstitutionalCampaignOnboardingData
    {
        $campaignLocation = $this->validated('campaign_location');
        $targetAudience = $this->validated('target_audience');

        return new InstitutionalCampaignOnboardingData(
            city: (string) $this->validated('city'),
            state: (string) $this->validated('state'),
            campaignName: (string) $this->validated('campaign_name'),
            campaignLocation: is_string($campaignLocation) ? $campaignLocation : null,
            targetAudience: is_string($targetAudience) ? $targetAudience : null,
            startDate: (string) $this->validated('start_date'),
            endDate: (string) $this->validated('end_date'),
            boothRequired: $this->boolean('booth_required'),
        );
    }
}
