<?php

namespace App\Queries\Institutional;

use App\Enums\InstitutionalBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class InstitutionalBeneficiaryQuery
{
    /**
     * @return array{beneficiaries: LengthAwarePaginator<int, WorkspaceBeneficiary>, counts: array<string, int>, campaigns: Collection<int, Campaign>}
     */
    public function get(Workspace $workspace, ?string $search, ?string $campaignSlug, ?InstitutionalBeneficiaryStatus $status): array
    {
        $query = $this->base($workspace)
            ->with('relatable')
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $like = "%{$search}%";
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('community', 'like', $like);
                });
            })
            ->when($campaignSlug !== null && $campaignSlug !== '', function (Builder $query) use ($workspace, $campaignSlug): void {
                $query->whereIn('relatable_id', Campaign::query()
                    ->whereBelongsTo($workspace)
                    ->where('slug', $campaignSlug)
                    ->select('id'));
            })
            ->when($status !== null, fn (Builder $query) => $query->whereRaw(<<<'SQL'
CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ?
SQL, [$status->value]))
            ->latest('id');

        $beneficiaries = $query->paginate(10)->withQueryString();
        $beneficiaries->getCollection()->each(function (WorkspaceBeneficiary $beneficiary): void {
            $beneficiary->setAttribute('institutional_status', $this->status($beneficiary)->value);
        });

        return [
            'beneficiaries' => $beneficiaries,
            'counts' => $this->counts($workspace),
            'campaigns' => Campaign::query()->whereBelongsTo($workspace)->orderBy('name')->get([
                'id', 'name', 'slug', 'location', 'status', 'ended_at', 'end_date', 'estimated_beneficiaries', 'beneficiary_limit',
            ]),
        ];
    }

    /** @return Builder<WorkspaceBeneficiary> */
    private function base(Workspace $workspace): Builder
    {
        return WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass());
    }

    /** @return array<string, int> */
    private function counts(Workspace $workspace): array
    {
        $row = $this->base($workspace)->toBase()
            ->selectRaw(<<<'SQL'
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS added,
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS invited,
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS registered,
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS active,
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS inactive,
COUNT(CASE WHEN CASE
    WHEN status = 'pending' THEN 'invited'
    WHEN status = 'suspended' THEN 'suspended'
    WHEN status IN ('revoked', 'declined', 'cancelled', 'expired') THEN 'inactive'
    WHEN status = 'active' AND source IN ('manual', 'import') AND beneficiary_id IS NULL THEN 'added'
    WHEN status = 'active' AND (beneficiary_id IS NOT NULL OR source = 'enrollment_code') THEN 'registered'
    ELSE 'active'
END = ? THEN 1 END) AS suspended
SQL, [
                InstitutionalBeneficiaryStatus::Added->value,
                InstitutionalBeneficiaryStatus::Invited->value,
                InstitutionalBeneficiaryStatus::Registered->value,
                InstitutionalBeneficiaryStatus::Active->value,
                InstitutionalBeneficiaryStatus::Inactive->value,
                InstitutionalBeneficiaryStatus::Suspended->value,
            ])
            ->first();

        return collect((array) $row)->map(static fn (mixed $value): int => (int) $value)->all();
    }

    private function status(WorkspaceBeneficiary $beneficiary): InstitutionalBeneficiaryStatus
    {
        return InstitutionalBeneficiaryStatus::from((string) $beneficiary->getAttribute('institutional_status'));
    }
}
