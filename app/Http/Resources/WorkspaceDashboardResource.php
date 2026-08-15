<?php

namespace App\Http\Resources;

use App\DTOs\Dashboard\ConsultationTrend;
use App\DTOs\Dashboard\DashboardSubscription;
use App\DTOs\Dashboard\DepartmentUtilization;
use App\DTOs\Dashboard\WorkspaceDashboard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceDashboard */
final class WorkspaceDashboardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'accountType' => $this->accountType->value,
            'beneficiaries' => [
                'total' => $this->totalBeneficiaries,
                'active' => $this->activeBeneficiaries,
                'capacity' => $this->capacity,
            ],
            'wallet' => [
                'balance' => $this->walletBalance,
                'currency' => $this->walletCurrency,
                'totalFunded' => $this->totalFunded,
            ],
            'subscription' => $this->subscriptionData($this->subscription),
            'coverage' => $this->coverage,
            'completedConsultations' => $this->completedConsultations,
            'departmentUtilization' => array_map(
                static fn (DepartmentUtilization $row): array => [
                    'department' => $row->department,
                    'gp' => $row->gp,
                    'specialist' => $row->specialist,
                ],
                $this->departmentUtilization,
            ),
            'consultationTrends' => array_map(
                static fn (ConsultationTrend $trend): array => [
                    'month' => $trend->month,
                    'consultations' => $trend->consultations,
                ],
                $this->consultationTrends,
            ),
            'recentActivities' => WorkspaceActivityResource::collection(
                $this->recentActivities,
            )->resolve($request),
        ];
    }

    /** @return array<string, mixed>|null */
    private function subscriptionData(?DashboardSubscription $subscription): ?array
    {
        if (! $subscription instanceof DashboardSubscription) {
            return null;
        }

        return [
            'planName' => $subscription->planName,
            'status' => $subscription->status,
            'statusLabel' => $subscription->statusLabel,
            'active' => $subscription->active,
            'renewalAmount' => $subscription->renewalAmount,
            'billingCycleLabel' => $subscription->billingCycleLabel,
            'includedCapacity' => $subscription->includedCapacity,
            'currentCapacity' => $subscription->currentCapacity,
            'additionalCapacity' => $subscription->additionalCapacity,
            'renewsAt' => $subscription->renewsAt?->toISOString(),
            'renewalDays' => $subscription->renewalDays,
        ];
    }
}
