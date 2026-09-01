<?php

namespace App\Queries\Dashboard;

use App\DTOs\Dashboard\InstitutionalDashboard;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\CampaignBoothChargeStatus;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\CampaignStatus;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignBeneficiaryImport;
use App\Models\CampaignBooth;
use App\Models\CampaignEnrollmentCode;
use App\Models\Consultations\Appointment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\InstitutionalCampaigns\CampaignMetricsQuery;
use App\Services\Activity\WorkspaceActivityAuthorizationService;
use App\Services\Activity\WorkspaceActivityQuery;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final readonly class InstitutionalDashboardQuery
{
    public function __construct(
        private CampaignMetricsQuery $metrics,
        private WorkspaceActivityAuthorizationService $activityAuthorization,
        private WorkspaceActivityQuery $workspaceActivities,
    ) {}

    public function execute(Workspace $workspace, User $user): InstitutionalDashboard
    {
        $campaigns = Campaign::query()
            ->whereBelongsTo($workspace)
            ->withCount('beneficiaries')
            ->orderByRaw("CASE status WHEN 'IN_PROGRESS' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'PENDING' THEN 3 ELSE 4 END")
            ->orderByDesc('start_date')
            ->get();
        $this->metrics->hydrate($campaigns);
        $wallet = $workspace->wallet()->firstOrFail(['id', 'balance', 'currency']);
        $allocated = 0.0;
        $utilized = 0.0;

        foreach ($campaigns as $campaign) {
            $financial = $this->financial($campaign);
            $utilized += (float) $financial['utilized'];

            if ($campaign->lifecycleStatus() !== CampaignStatus::COMPLETED) {
                $allocated += (float) $financial['reserved'];
            }
        }

        $beneficiaryQuery = $workspace->workspaceBeneficiaries();
        $beneficiaries = [
            'total' => (clone $beneficiaryQuery)->count(),
            'active' => (clone $beneficiaryQuery)->where('status', WorkspaceBeneficiaryStatus::Active)->count(),
        ];

        return new InstitutionalDashboard(
            funding: [
                'currency' => $wallet->currency,
                'availableBalance' => $wallet->balance,
                'allocatedBalance' => number_format($allocated, 2, '.', ''),
                'utilized' => number_format($utilized, 2, '.', ''),
            ],
            beneficiaries: $beneficiaries,
            booths: $this->booths($workspace, $wallet->balance, $wallet->currency),
            campaignPerformance: $this->campaignPerformance($campaigns),
            consultations: $this->consultationTotals($campaigns),
            consultationTrends: $this->consultationTrends($workspace),
            activities: $this->activities($workspace, $user),
            remainingCampaigns: $this->remainingCampaigns($campaigns),
        );
    }

    /** @return array<string, mixed> */
    private function booths(Workspace $workspace, string $walletBalance, string $currency): array
    {
        $booths = CampaignBooth::query()
            ->where('workspace_id', $workspace->getKey())
            ->with([
                'campaign:id,name,slug,end_date',
                'recurringCosts' => static fn ($query) => $query
                    ->where('category', CampaignRecurringCostCategory::BoothService)
                    ->with(['charges' => static fn ($query) => $query
                        ->where('status', CampaignBoothChargeStatus::Pending)]),
            ])
            ->get();
        $operational = $booths->whereIn('status', [CampaignBoothStatus::Active, CampaignBoothStatus::GracePeriod])->count();
        $active = $booths->where('status', CampaignBoothStatus::Active);
        $monthly = $active->sum(static fn (CampaignBooth $booth): float => (float) $booth->monthly_fee);
        $outstanding = $booths->sum(function (CampaignBooth $booth): float {
            $cost = $booth->recurringCosts->first();
            $charge = $cost === null ? null : $cost->charges->first();

            return $charge === null ? 0.0 : (float) $charge->amount;
        });
        $nextDeduction = $active
            ->map(static fn (CampaignBooth $booth): ?string => $booth->recurringCosts->first()?->next_charge_on?->toDateString())
            ->filter()
            ->sort()
            ->first();

        $rows = $booths->sortBy(function (CampaignBooth $booth): string {
            $rank = match ($booth->status) {
                CampaignBoothStatus::Suspended => '0',
                CampaignBoothStatus::GracePeriod => '1',
                CampaignBoothStatus::Active => '2',
                CampaignBoothStatus::Requested => '3',
                CampaignBoothStatus::Inactive => '4',
            };

            return $rank.'-'.($booth->recurringCosts->first()?->next_charge_on?->toDateString() ?? '9999-12-31');
        })->take(6)->map(function (CampaignBooth $booth): array {
            $cost = $booth->recurringCosts->first();
            $pending = $cost?->charges->first();

            return [
                'id' => $booth->public_id,
                'name' => $booth->name,
                'community' => $booth->community,
                'campaignName' => $booth->campaign->name,
                'campaignSlug' => $booth->campaign->slug,
                'status' => $booth->status->value,
                'statusLabel' => $booth->status === CampaignBoothStatus::Requested
                    ? 'Deployment scheduled'
                    : $booth->status->label(),
                'activatedAt' => $booth->activated_at?->toDateString(),
                'nextDeduction' => in_array($booth->status, [
                    CampaignBoothStatus::Active,
                    CampaignBoothStatus::GracePeriod,
                    CampaignBoothStatus::Suspended,
                ], true) ? $cost?->next_charge_on?->toDateString() : null,
                'monthlyFee' => $booth->monthly_fee,
                'outstandingAmount' => $pending?->amount,
                'graceEndsOn' => $booth->billing_grace_ends_on?->toDateString(),
                'campaignEndsOn' => $booth->campaign->end_date?->toDateString(),
            ];
        })->values()->all();

        return [
            'summary' => [
                'operational' => $operational,
                'awaitingDeployment' => $booths->where('status', CampaignBoothStatus::Requested)->count(),
                'monthlyServiceCost' => number_format($monthly, 2, '.', ''),
                'serviceUnitFee' => (string) config('campaigns.booth_monthly_fee'),
                'nextDeduction' => $nextDeduction,
                'walletBalance' => $walletBalance,
                'currency' => $currency,
                'delinquentCount' => $booths->whereIn('status', [CampaignBoothStatus::GracePeriod, CampaignBoothStatus::Suspended])->count(),
                'outstandingAmount' => number_format($outstanding, 2, '.', ''),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @return list<array<string, mixed>>
     */
    private function campaignPerformance(Collection $campaigns): array
    {
        return array_values($campaigns->where('status', '!=', CampaignStatus::COMPLETED)->take(5)->map(function (Campaign $campaign): array {
            $financial = $this->financial($campaign);

            return [
                'id' => (int) $campaign->getKey(),
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'status' => $campaign->lifecycleStatus()->value,
                'statusLabel' => $campaign->lifecycleStatus()->label(),
                'allocated' => $financial['allocated'],
                'utilized' => $financial['utilized'],
                'remaining' => $financial['reserved'],
                'people' => (int) $campaign->beneficiaries_count,
            ];
        })->values()->all());
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @return array{gp: array{allocated: int, used: int, reserved: int, remaining: int}, specialist: array{allocated: int, used: int, reserved: int, remaining: int}}
     */
    private function consultationTotals(Collection $campaigns): array
    {
        $totals = ['gp' => ['allocated' => 0, 'used' => 0, 'reserved' => 0, 'remaining' => 0], 'specialist' => ['allocated' => 0, 'used' => 0, 'reserved' => 0, 'remaining' => 0]];

        foreach ($campaigns->where('status', '!=', CampaignStatus::COMPLETED) as $campaign) {
            $metrics = $this->financial($campaign)['consultations'];

            foreach (['gp', 'specialist'] as $type) {
                $totals[$type]['allocated'] += (int) $metrics[$type]['units'];
                $totals[$type]['used'] += (int) $metrics[$type]['confirmed'];
                $totals[$type]['reserved'] += (int) $metrics[$type]['reserved'];
                $totals[$type]['remaining'] += (int) $metrics[$type]['remaining'];
            }
        }

        return $totals;
    }

    /** @return list<array{month: string, consultations: int}> */
    private function consultationTrends(Workspace $workspace): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $expression = 'COALESCE(completed_at, created_at)';
        $counts = Appointment::query()
            ->sponsoredBy($workspace)
            ->where('status', AppointmentStatus::Completed)
            ->whereRaw("{$expression} >= ?", [$start])
            ->selectRaw("DATE_FORMAT({$expression}, '%Y-%m') AS period, COUNT(*) AS aggregate")
            ->groupBy('period')
            ->pluck('aggregate', 'period');

        return array_values(collect(range(0, 5))->map(function (int $offset) use ($start, $counts): array {
            $month = $start->addMonths($offset);

            return ['month' => $month->format('M'), 'consultations' => (int) $counts->get($month->format('Y-m'), 0)];
        })->all());
    }

    /** @return list<array<string, mixed>> */
    private function activities(Workspace $workspace, User $user): array
    {
        $items = collect();

        if ($this->activityAuthorization->canView($user, $workspace)) {
            $items = $items->concat($this->workspaceActivities->recent($workspace, $user, 8)->map(function (DatabaseNotification $notification): array {
                $data = $notification->data;

                return [
                    'id' => 'notification-'.$notification->getKey(),
                    'title' => (string) ($data['title'] ?? 'Workspace activity'),
                    'actorName' => (string) data_get($data, 'actor.name', 'HealthBubba System'),
                    'occurredAt' => $notification->created_at?->toISOString(),
                    'timestamp' => $notification->created_at?->getTimestamp() ?? 0,
                ];
            }));
        }

        $imports = CampaignBeneficiaryImport::query()->where('workspace_id', $workspace->getKey())->with('campaign:id,name')->latest()->limit(8)->get();
        $codes = CampaignEnrollmentCode::query()->whereHas('campaign', static fn ($query) => $query->where('workspace_id', $workspace->getKey()))->with('campaign:id,name')->latest()->limit(8)->get();
        $allocations = Transaction::query()
            ->where('owner_type', $workspace->getMorphClass())
            ->where('owner_id', $workspace->getKey())
            ->where('type', TransactionTypes::CAMPAIGN_ALLOCATION)
            ->where('status', TransactionStatus::COMPLETED)
            ->latest()->limit(8)->get();
        $endedCampaigns = Campaign::query()
            ->whereBelongsTo($workspace)
            ->whereNotNull('ended_at')
            ->latest('ended_at')
            ->limit(8)
            ->get(['id', 'name', 'currency', 'returned_amount', 'ended_at']);
        $actorIds = $imports->pluck('created_by_user_id')
            ->concat($codes->pluck('created_by_user_id'))
            ->concat($allocations->pluck('meta')->map(static fn (?array $meta): mixed => $meta['created_by_user_id'] ?? $meta['created_by'] ?? null))
            ->filter()
            ->unique();
        $actors = User::query()->whereKey($actorIds->all())->pluck('name', 'id');
        $campaignNames = Campaign::query()
            ->whereBelongsTo($workspace)
            ->whereKey($allocations->pluck('meta')->map(static fn (?array $meta): mixed => $meta['campaign_id'] ?? null)->filter()->all())
            ->pluck('name', 'id');

        foreach ($imports as $import) {
            $items->push([
                'id' => 'beneficiary-import-'.$import->getKey(),
                'title' => "Bulk uploaded {$import->processed_count} beneficiaries into {$import->campaign->name} ({$import->enrolled_count} committed, {$import->skipped_count} errors)",
                'actorName' => $actors->get($import->created_by_user_id, 'HealthBubba System'),
                'occurredAt' => $import->created_at?->toISOString(),
                'timestamp' => $import->created_at?->getTimestamp() ?? 0,
            ]);
        }

        foreach ($codes as $code) {
            $items->push([
                'id' => 'enrollment-code-'.$code->getKey(),
                'title' => "Created enrollment code {$code->code} for {$code->campaign->name}",
                'actorName' => $actors->get($code->created_by_user_id, 'HealthBubba System'),
                'occurredAt' => $code->created_at?->toISOString(),
                'timestamp' => $code->created_at?->getTimestamp() ?? 0,
            ]);
        }

        foreach ($allocations as $transaction) {
            $campaignName = $campaignNames->get(data_get($transaction->meta, 'campaign_id'));
            $actorId = data_get($transaction->meta, 'created_by_user_id', data_get($transaction->meta, 'created_by'));
            $items->push([
                'id' => 'allocation-'.$transaction->getKey(),
                'title' => 'Allocated '.$this->money($transaction->amount, $transaction->currency).' to '.($campaignName ?? 'a campaign'),
                'actorName' => $actors->get($actorId, 'HealthBubba System'),
                'occurredAt' => $transaction->created_at?->toISOString(),
                'timestamp' => $transaction->created_at?->getTimestamp() ?? 0,
            ]);
        }

        foreach ($endedCampaigns as $campaign) {
            $items->push([
                'id' => 'campaign-ended-'.$campaign->getKey(),
                'title' => 'Ended '.$campaign->name.' — '.$this->money($campaign->returned_amount, $campaign->currency).' unused allocation returned to wallet',
                'actorName' => 'HealthBubba System',
                'occurredAt' => $campaign->ended_at?->toISOString(),
                'timestamp' => $campaign->ended_at?->getTimestamp() ?? 0,
            ]);
        }

        return array_values($items->unique('id')->sortByDesc('timestamp')->take(4)->map(function (array $item): array {
            unset($item['timestamp']);

            return $item;
        })->values()->all());
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @return list<array<string, mixed>>
     */
    private function remainingCampaigns(Collection $campaigns): array
    {
        return array_values($campaigns->where('status', '!=', CampaignStatus::COMPLETED)->take(3)->map(function (Campaign $campaign): array {
            $financial = $this->financial($campaign);

            return [
                'id' => (int) $campaign->getKey(),
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'currency' => $campaign->currency,
                'gp' => $financial['consultations']['gp'],
                'specialist' => $financial['consultations']['specialist'],
                'medication' => $financial['budgets']['medication'],
            ];
        })->values()->all());
    }

    /** @return array<string, mixed> */
    private function financial(Campaign $campaign): array
    {
        return (array) $campaign->getAttribute('financial_metrics');
    }

    private function money(string $amount, string $currency): string
    {
        $symbol = $currency === 'NGN' ? '₦' : $currency.' ';

        return $symbol.number_format((float) $amount, 0);
    }
}
