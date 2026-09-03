<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\RecordCampaignUsageData;
use App\Enums\CampaignUsageBenefit;
use App\Enums\CampaignUsageSource;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Consultations\Consultation;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordCampaignUsageAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    public function execute(RecordCampaignUsageData $data): CampaignUsageEntry
    {
        return DB::transaction(function () use ($data): CampaignUsageEntry {
            $campaign = Campaign::query()
                ->whereBelongsTo($data->workspace)
                ->whereKey($data->campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $campaign->isActive()) {
                throw ValidationException::withMessages(['campaign' => 'Usage can only be recorded for an active campaign.']);
            }

            if ($data->beneficiary->status !== WorkspaceBeneficiaryStatus::Active) {
                throw ValidationException::withMessages(['beneficiary_id' => 'Select an active campaign beneficiary.']);
            }

            [$quantity, $unitAmount, $total] = $this->amounts($campaign, $data);
            $this->ensureAvailable($campaign, $data->benefit, $quantity, $total);

            return CampaignUsageEntry::query()->create([
                'campaign_id' => $campaign->getKey(),
                'workspace_id' => $data->workspace->getKey(),
                'workspace_beneficiary_id' => $data->beneficiary->getKey(),
                'recorded_by_user_id' => $data->user->getKey(),
                'benefit' => $data->benefit,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount?->toMajorAmount(),
                'total_amount' => $total->toMajorAmount(),
                'currency' => $total->currency,
                'source' => CampaignUsageSource::Manual,
                'reference' => $this->references->generateCampaignUsage(),
                'occurred_at' => now(),
            ]);
        }, 3);
    }

    /** @return array{0: int|null, 1: Money|null, 2: Money} */
    private function amounts(Campaign $campaign, RecordCampaignUsageData $data): array
    {
        if (in_array($data->benefit, [CampaignUsageBenefit::GeneralPractitioner, CampaignUsageBenefit::Specialist], true)) {
            $quantity = $data->quantity ?? 0;
            $fee = Money::fromMajor(
                $data->benefit === CampaignUsageBenefit::GeneralPractitioner
                    ? ($campaign->gp_fee ?? '0.00')
                    : ($campaign->specialist_fee ?? '0.00'),
                $campaign->currency,
            );

            return [$quantity, $fee, $fee->multiply($quantity)];
        }

        return [null, null, Money::fromMajor($data->amount ?? '0.00', $campaign->currency)];
    }

    private function ensureAvailable(Campaign $campaign, CampaignUsageBenefit $benefit, ?int $quantity, Money $total): void
    {
        if (in_array($benefit, [CampaignUsageBenefit::GeneralPractitioner, CampaignUsageBenefit::Specialist], true)) {
            $type = $benefit === CampaignUsageBenefit::GeneralPractitioner
                ? ConsultationType::GeneralPractitioner
                : ConsultationType::Specialist;
            $allocated = (int) $campaign->consultationQuotas()->where('consultation_type', $type)->sum('quantity');
            $used = (int) $campaign->usageEntries()->where('benefit', $benefit)->sum('quantity');
            $reserved = Consultation::query()
                ->whereIn('workspace_beneficiary_id', $campaign->beneficiaries()->select('id'))
                ->where('consultation_type', $type)
                ->where('status', ConsultationReservationStatus::Reserved)
                ->count();

            if (($quantity ?? 0) > max(0, $allocated - $used - $reserved)) {
                throw ValidationException::withMessages(['quantity' => 'This usage exceeds the remaining consultation allocation.']);
            }

            return;
        }

        $allocated = Money::fromMajor(
            $benefit === CampaignUsageBenefit::Medication ? $campaign->medication_budget : $campaign->laboratory_budget,
            $campaign->currency,
        );
        $used = Money::fromMajor(
            (string) $campaign->usageEntries()->where('benefit', $benefit)->sum('total_amount'),
            $campaign->currency,
        );

        if ($used->amountInMinorUnits + $total->amountInMinorUnits > $allocated->amountInMinorUnits) {
            throw ValidationException::withMessages(['amount' => 'This usage exceeds the remaining campaign budget.']);
        }
    }
}
