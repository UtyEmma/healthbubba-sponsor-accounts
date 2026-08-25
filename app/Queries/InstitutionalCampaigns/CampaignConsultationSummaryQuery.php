<?php

namespace App\Queries\InstitutionalCampaigns;

use App\DTOs\Campaigns\CampaignConsultationSummaryData;
use App\DTOs\Consultations\ConsultationCoverageSummary;
use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\Consultations\Consultation;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class CampaignConsultationSummaryQuery
{
    public function get(Campaign $campaign, Workspace $workspace): CampaignConsultationSummaryData
    {
        return $this->summarize($workspace, $campaign);
    }

    public function getForWorkspace(Workspace $workspace): CampaignConsultationSummaryData
    {
        return $this->summarize($workspace);
    }

    private function summarize(
        Workspace $workspace,
        ?Campaign $campaign = null,
    ): CampaignConsultationSummaryData {
        $campaignName = $campaign instanceof Campaign
            ? $campaign->name
            : 'Campaign coverage';
        $purchases = CampaignConsultationQuota::query()
            ->whereBelongsTo($workspace)
            ->when(
                $campaign instanceof Campaign,
                fn ($query) => $query->whereBelongsTo($campaign),
            )
            ->selectRaw('consultation_type, SUM(quantity) as purchased, SUM(total_cost) as spent')
            ->groupBy('consultation_type')
            ->get()
            ->keyBy(static fn (CampaignConsultationQuota $quota): string => $quota->consultation_type->value);

        $beneficiaries = WorkspaceBeneficiary::query()
            ->select('id')
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->when(
                $campaign instanceof Campaign,
                fn ($query) => $query->where('relatable_id', $campaign->getKey()),
            );

        $usage = Consultation::query()
            ->whereBelongsTo($workspace)
            ->whereIn('workspace_beneficiary_id', $beneficiaries)
            ->whereIn('status', [
                ConsultationReservationStatus::Reserved,
                ConsultationReservationStatus::Confirmed,
            ])
            ->selectRaw('consultation_type, status, COUNT(*) as aggregate')
            ->groupBy('consultation_type', 'status')
            ->get()
            ->groupBy(static fn (Consultation $consultation): string => $consultation->consultation_type->value)
            ->toBase();

        $allocations = array_map(
            fn (ConsultationType $type): array => $this->allocation(
                $type,
                $purchases,
                $usage,
            ),
            ConsultationType::cases(),
        );

        $gpSpent = (string) ($purchases->get(ConsultationType::GeneralPractitioner->value)?->getAttribute('spent') ?? '0.00');
        $specialistSpent = (string) ($purchases->get(ConsultationType::Specialist->value)?->getAttribute('spent') ?? '0.00');
        $wallet = $workspace->wallet()->firstOrNew([], [
            'balance' => '0.00',
            'currency' => 'NGN',
        ]);

        return new CampaignConsultationSummaryData(
            campaignName: $campaignName,
            activeBeneficiaries: WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('relatable_type', (new Campaign)->getMorphClass())
                ->when(
                    $campaign instanceof Campaign,
                    fn ($query) => $query->where('relatable_id', $campaign->getKey()),
                )
                ->where('status', WorkspaceBeneficiaryStatus::Active)
                ->count(),
            allocations: $allocations,
            currency: $wallet->currency,
            walletBalance: $wallet->balance,
            gpSpent: $gpSpent,
            specialistSpent: $specialistSpent,
            totalSpent: $this->addDecimalAmounts($gpSpent, $specialistSpent),
        );
    }

    /**
     * @param  EloquentCollection<string, CampaignConsultationQuota>  $purchases
     * @param  Collection<int|string, EloquentCollection<int, Consultation>>  $usage
     * @return array<string, mixed>
     */
    private function allocation(
        ConsultationType $type,
        EloquentCollection $purchases,
        Collection $usage,
    ): array {
        $purchased = (int) ($purchases->get($type->value)?->getAttribute('purchased') ?? 0);
        $typeUsage = $usage->get($type->value, collect());
        $completed = (int) $typeUsage
            ->firstWhere('status', ConsultationReservationStatus::Confirmed)
            ?->getAttribute('aggregate');
        $reserved = (int) $typeUsage
            ->firstWhere('status', ConsultationReservationStatus::Reserved)
            ?->getAttribute('aggregate');

        return (new ConsultationCoverageSummary(
            type: $type,
            scope: ConsultationAllocationScope::Shared,
            limit: $purchased,
            completed: $completed,
            reserved: $reserved,
            resetAt: null,
        ))->toArray();
    }

    private function addDecimalAmounts(string $first, string $second): string
    {
        return Money::fromMajor($first)
            ->add(Money::fromMajor($second))
            ->toMajorAmount();
    }
}
