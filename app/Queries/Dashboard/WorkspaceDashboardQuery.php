<?php

namespace App\Queries\Dashboard;

use App\DTOs\CapacityPurchases\CapacityConfiguration;
use App\DTOs\Dashboard\ConsultationTrend;
use App\DTOs\Dashboard\DashboardSubscription;
use App\DTOs\Dashboard\DepartmentUtilization;
use App\DTOs\Dashboard\WorkspaceDashboard;
use App\Enums\AccountTypes;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Activity\WorkspaceActivityAuthorizationService;
use App\Services\Activity\WorkspaceActivityQuery;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Payments\CapacityPricingService;
use App\Services\Payments\PlanPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class WorkspaceDashboardQuery
{
    public function __construct(
        private ConsultationCoverageService $coverage,
        private CapacityPricingService $capacityPricing,
        private PlanPricingService $planPricing,
        private WorkspaceActivityAuthorizationService $activityAuthorization,
        private WorkspaceActivityQuery $activities,
    ) {}

    public function execute(Workspace $workspace, User $user): WorkspaceDashboard
    {
        $subscription = $workspace->type === AccountTypes::INSTITUTION
            ? null
            : $this->subscription($workspace);
        $coverage = $this->coverage->summary($workspace);
        $wallet = $workspace->wallet()->firstOrFail();
        $walletBalance = $wallet->balance;
        $walletCurrency = $wallet->currency;
        $activeBeneficiaries = $workspace->workspaceBeneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->count();
        $totalBeneficiaries = $workspace->workspaceBeneficiaries()
            ->consumingCapacity()
            ->count();
        $capacity = $subscription instanceof Subscription
            ? $this->capacityPricing->currentCapacity($subscription)
            : 0;
        $completedConsultations = collect($coverage['allocations'])
            ->sum(static fn (mixed $allocation): int => (int) data_get($allocation, 'completed', 0));

        return new WorkspaceDashboard(
            accountType: $workspace->type,
            totalBeneficiaries: $totalBeneficiaries,
            activeBeneficiaries: $activeBeneficiaries,
            capacity: $capacity,
            walletBalance: $walletBalance,
            walletCurrency: $walletCurrency,
            totalFunded: $this->totalFunded($workspace),
            subscription: $this->subscriptionData($subscription),
            coverage: $coverage,
            completedConsultations: $completedConsultations,
            departmentUtilization: $workspace->type === AccountTypes::BUSINESS
                ? $this->departmentUtilization($workspace)
                : [],
            consultationTrends: $workspace->type === AccountTypes::INSTITUTION
                ? $this->consultationTrends($workspace)
                : [],
            recentActivities: $this->recentActivities($workspace, $user),
        );
    }

    private function subscription(Workspace $workspace): ?Subscription
    {
        return Subscription::query()
            ->with(['plan.features', 'scheduledPlan.features'])
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->latest('id')
            ->first();
    }

    private function subscriptionData(?Subscription $subscription): ?DashboardSubscription
    {
        if (! $subscription instanceof Subscription) {
            return null;
        }

        $configuration = $this->capacityPricing->configuration($subscription->plan);
        $currentCapacity = $this->capacityPricing->currentCapacity($subscription);
        $includedCapacity = $configuration instanceof CapacityConfiguration
            ? $configuration->includedCapacity
            : $currentCapacity;
        $renewsAt = $this->renewalDate($subscription);

        try {
            $renewalAmount = $this->planPricing
                ->renewalForPlan($subscription, $subscription->scheduledPlan ?? $subscription->plan)
                ->money
                ->toMajorAmount();
        } catch (CheckoutUnavailable) {
            $renewalAmount = $subscription->plan->price;
        }

        return new DashboardSubscription(
            planName: $subscription->plan->name,
            status: $subscription->status->value,
            statusLabel: Str::of($subscription->status->value)->replace('_', ' ')->title()->toString(),
            active: $subscription->valid(),
            renewalAmount: $renewalAmount,
            billingCycleLabel: $this->billingCycleLabel($subscription),
            includedCapacity: $includedCapacity,
            currentCapacity: $currentCapacity,
            additionalCapacity: max(0, $currentCapacity - $includedCapacity),
            renewsAt: $renewsAt,
            renewalDays: $this->renewalDays($renewsAt),
        );
    }

    private function renewalDate(Subscription $subscription): ?CarbonImmutable
    {
        $renewalDate = $subscription->status === SubscriptionStatus::Trialing
            ? $subscription->trial_ends_at
            : ($subscription->next_charge_at ?? $subscription->ends_at);

        return $renewalDate?->toImmutable();
    }

    private function renewalDays(?CarbonImmutable $renewsAt): ?int
    {
        if (! $renewsAt instanceof CarbonImmutable || $renewsAt->isPast()) {
            return null;
        }

        return (int) CarbonImmutable::now()
            ->startOfDay()
            ->diffInDays($renewsAt->startOfDay());
    }

    private function billingCycleLabel(Subscription $subscription): string
    {
        $period = $subscription->plan->billing_period;
        $interval = $subscription->plan->billing_interval;

        if ($period !== 1) {
            return "Every {$period} ".Str::plural($interval->value, $period);
        }

        return match ($interval) {
            Interval::Day => 'Daily',
            Interval::Week => 'Weekly',
            Interval::Month => 'Monthly',
            Interval::Year => 'Yearly',
        };
    }

    private function totalFunded(Workspace $workspace): string
    {
        $total = Transaction::query()
            ->where('owner_type', $workspace->getMorphClass())
            ->where('owner_id', $workspace->getKey())
            ->where('status', TransactionStatus::COMPLETED)
            ->where('flow', TransactionFlow::CREDIT)
            ->sum('amount');

        return (string) $total;
    }

    /** @return list<DepartmentUtilization> */
    private function departmentUtilization(Workspace $workspace): array
    {
        $departments = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->pluck('department', 'id');
        $usage = Consultation::query()
            ->whereBelongsTo($workspace)
            ->where('status', ConsultationReservationStatus::Confirmed)
            ->selectRaw('workspace_beneficiary_id, consultation_type, COUNT(*) AS aggregate')
            ->groupBy('workspace_beneficiary_id', 'consultation_type')
            ->get();
        $totals = [];

        foreach ($usage as $row) {
            $department = $departments->get($row->workspace_beneficiary_id);
            $department = is_string($department) && $department !== '' ? $department : 'Unassigned';
            $totals[$department] ??= ['gp' => 0, 'specialist' => 0];
            $key = $row->consultation_type === ConsultationType::GeneralPractitioner
                ? 'gp'
                : 'specialist';
            $totals[$department][$key] += (int) $row->getAttribute('aggregate');
        }

        return array_values(collect($totals)
            ->map(static fn (array $values, string $department): DepartmentUtilization => new DepartmentUtilization(
                department: $department,
                gp: $values['gp'],
                specialist: $values['specialist'],
            ))
            ->sortByDesc(static fn (DepartmentUtilization $row): int => $row->gp + $row->specialist)
            ->take(5)
            ->values()
            ->all());
    }

    /** @return list<ConsultationTrend> */
    private function consultationTrends(Workspace $workspace): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $periodExpression = 'COALESCE(completed_at, created_at)';
        $counts = Appointment::query()
            ->sponsoredBy($workspace)
            ->where('status', AppointmentStatus::Completed)
            ->whereRaw("{$periodExpression} >= ?", [$start])
            ->selectRaw("DATE_FORMAT({$periodExpression}, '%Y-%m') AS period, COUNT(*) AS aggregate")
            ->groupBy('period')
            ->pluck('aggregate', 'period');

        return array_values(collect(range(0, 5))
            ->map(function (int $offset) use ($start, $counts): ConsultationTrend {
                $month = $start->addMonths($offset);

                return new ConsultationTrend(
                    month: $month->format('M'),
                    consultations: (int) $counts->get($month->format('Y-m'), 0),
                );
            })
            ->all());
    }

    /** @return Collection<int, DatabaseNotification> */
    private function recentActivities(Workspace $workspace, User $user): Collection
    {
        if (! $this->activityAuthorization->canView($user, $workspace)) {
            return new Collection;
        }

        return $this->activities->recent($workspace, $user, 3);
    }
}
