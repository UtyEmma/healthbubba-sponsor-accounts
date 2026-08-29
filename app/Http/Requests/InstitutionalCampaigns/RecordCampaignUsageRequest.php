<?php

namespace App\Http\Requests\InstitutionalCampaigns;

use App\DTOs\Campaigns\RecordCampaignUsageData;
use App\Enums\CampaignUsageBenefit;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

final class RecordCampaignUsageRequest extends ManageInstitutionalCampaignRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'benefit' => ['required', Rule::enum(CampaignUsageBenefit::class)],
            'beneficiary_id' => [
                'required',
                'integer',
                Rule::exists('workspace_beneficiaries', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('workspace_id', $this->workspace()->getKey())
                        ->where('relatable_type', $this->campaign()->getMorphClass())
                        ->where('relatable_id', $this->campaign()->getKey()),
                ),
            ],
            'quantity' => ['nullable', 'required_if:benefit,gp,specialist', 'integer', 'min:1', 'max:100000'],
            'amount' => ['nullable', 'required_if:benefit,medication,laboratory', 'numeric', 'min:0.01', 'max:100000000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('amount'))) {
            $this->merge(['amount' => str_replace([',', ' '], '', (string) $this->input('amount'))]);
        }
    }

    public function dto(): RecordCampaignUsageData
    {
        $beneficiary = WorkspaceBeneficiary::query()
            ->whereBelongsTo($this->workspace())
            ->where('relatable_type', $this->campaign()->getMorphClass())
            ->where('relatable_id', $this->campaign()->getKey())
            ->findOrFail((int) $this->validated('beneficiary_id'));

        return new RecordCampaignUsageData(
            workspace: $this->workspace(),
            user: $this->onboardingUser(),
            campaign: $this->campaign(),
            beneficiary: $beneficiary,
            benefit: CampaignUsageBenefit::from((string) $this->validated('benefit')),
            quantity: $this->validated('quantity') === null ? null : (int) $this->validated('quantity'),
            amount: $this->validated('amount') === null ? null : (string) $this->validated('amount'),
        );
    }
}
