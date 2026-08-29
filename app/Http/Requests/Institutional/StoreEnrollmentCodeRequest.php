<?php

namespace App\Http\Requests\Institutional;

use App\DTOs\EnrollmentCodes\CreateCampaignEnrollmentCodeData;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use App\Models\Campaign;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreEnrollmentCodeRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    protected bool $manageOnly = true;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'campaign' => ['required', 'string', Rule::exists('campaigns', 'slug')->where('workspace_id', $this->workspace()->getKey())],
            'enrollment_limit' => ['required', 'integer', 'min:1', 'max:1000000'],
            'expires_at' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('campaign') || $validator->errors()->has('expires_at')) {
                return;
            }

            $campaign = $this->campaign();
            $expiresAt = CarbonImmutable::parse((string) $this->input('expires_at'));

            if ($campaign->end_date !== null && $expiresAt->isAfter($campaign->end_date)) {
                $validator->errors()->add('expires_at', 'The code cannot expire after the campaign ends.');
            }
        }];
    }

    public function campaign(): Campaign
    {
        return Campaign::query()
            ->whereBelongsTo($this->workspace())
            ->where('slug', (string) $this->validated('campaign'))
            ->firstOrFail();
    }

    public function enrollmentCodeData(): CreateCampaignEnrollmentCodeData
    {
        return new CreateCampaignEnrollmentCodeData(
            campaign: $this->campaign(),
            creator: $this->onboardingUser(),
            enrollmentLimit: (int) $this->validated('enrollment_limit'),
            expiresAt: CarbonImmutable::parse((string) $this->validated('expires_at')),
        );
    }
}
