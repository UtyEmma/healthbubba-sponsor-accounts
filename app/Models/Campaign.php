<?php

namespace App\Models;

use App\Enums\CampaignEnrollmentMethod;
use App\Enums\CampaignStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $country
 * @property string|null $city
 * @property string|null $state
 * @property string|null $location
 * @property string|null $target_audience
 * @property int|null $beneficiary_limit
 * @property CampaignEnrollmentMethod|null $enrollment_method
 * @property int|null $estimated_beneficiaries
 * @property bool $booth_required
 * @property string|null $gp_fee
 * @property string|null $specialist_fee
 * @property string $currency
 * @property string $medication_budget
 * @property string $laboratory_budget
 * @property string|null $allocation_reference
 * @property string $returned_amount
 * @property CampaignStatus $status
 * @property int|null $booth_count
 * @property Carbon|null $booth_preferred_deployment_date
 * @property string|null $booth_site
 * @property string|null $booth_contact_name
 * @property string|null $booth_contact_phone
 * @property string|null $booth_setup_unit_fee
 * @property string|null $booth_monthly_unit_fee
 * @property Carbon|null $booth_activated_at
 * @property Carbon|null $booth_deactivated_at
 * @property Carbon|null $booth_last_billed_at
 * @property Carbon|null $launched_at
 * @property Carbon|null $paused_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Collection<int, WorkspaceBeneficiary> $beneficiaries
 * @property-read Collection<int, CampaignConsultationQuota> $consultationQuotas
 * @property-read Collection<int, CampaignBudgetUsage> $budgetUsages
 * @property-read Collection<int, CampaignBoothCharge> $boothCharges
 */
final class Campaign extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'beneficiary_limit' => null,
        'status' => CampaignStatus::PENDING->value,
        'currency' => 'NGN',
        'medication_budget' => '0.00',
        'laboratory_budget' => '0.00',
        'returned_amount' => '0.00',
    ];

    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'slug',
        'location',
        'state',
        'city',
        'country',
        'target_audience',
        'enrollment_method',
        'estimated_beneficiaries',
        'beneficiary_limit',
        'booth_required',
        'gp_fee',
        'specialist_fee',
        'currency',
        'medication_budget',
        'laboratory_budget',
        'allocation_reference',
        'returned_amount',
        'launched_at',
        'paused_at',
        'ended_at',
        'booth_count',
        'booth_preferred_deployment_date',
        'booth_site',
        'booth_contact_name',
        'booth_contact_phone',
        'booth_setup_unit_fee',
        'booth_monthly_unit_fee',
        'booth_activated_at',
        'booth_deactivated_at',
        'booth_last_billed_at',
        'start_date',
        'end_date',
        'status',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return MorphMany<WorkspaceBeneficiary, $this> */
    public function beneficiaries(): MorphMany
    {
        return $this->morphMany(WorkspaceBeneficiary::class, 'relatable');
    }

    /** @return MorphMany<WorkspaceBeneficiary, $this> */
    public function activeBeneficiaries(): MorphMany
    {
        return $this->beneficiaries()->where(
            'status',
            WorkspaceBeneficiaryStatus::Active,
        );
    }

    /** @return HasMany<CampaignConsultationQuota, $this> */
    public function consultationQuotas(): HasMany
    {
        return $this->hasMany(CampaignConsultationQuota::class);
    }

    /** @return HasMany<CampaignBudgetUsage, $this> */
    public function budgetUsages(): HasMany
    {
        return $this->hasMany(CampaignBudgetUsage::class);
    }

    /** @return HasMany<CampaignBoothCharge, $this> */
    public function boothCharges(): HasMany
    {
        return $this->hasMany(CampaignBoothCharge::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lifecycleStatus(): CampaignStatus
    {
        $today = CarbonImmutable::today();

        if ($this->status === CampaignStatus::COMPLETED
            || $this->ended_at !== null
            || $this->end_date?->isBefore($today) === true) {
            return CampaignStatus::COMPLETED;
        }

        if ($this->status === CampaignStatus::PAUSED) {
            return CampaignStatus::PAUSED;
        }

        if ($this->start_date?->isAfter($today) === true) {
            return CampaignStatus::PENDING;
        }

        return CampaignStatus::IN_PROGRESS;
    }

    public function isActive(): bool
    {
        return $this->lifecycleStatus() === CampaignStatus::IN_PROGRESS;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'booth_required' => 'boolean',
            'beneficiary_limit' => 'integer',
            'estimated_beneficiaries' => 'integer',
            'enrollment_method' => CampaignEnrollmentMethod::class,
            'status' => CampaignStatus::class,
            'gp_fee' => 'decimal:2',
            'specialist_fee' => 'decimal:2',
            'medication_budget' => 'decimal:2',
            'laboratory_budget' => 'decimal:2',
            'returned_amount' => 'decimal:2',
            'booth_count' => 'integer',
            'booth_setup_unit_fee' => 'decimal:2',
            'booth_monthly_unit_fee' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'launched_at' => 'datetime',
            'paused_at' => 'datetime',
            'ended_at' => 'datetime',
            'booth_preferred_deployment_date' => 'date',
            'booth_activated_at' => 'datetime',
            'booth_deactivated_at' => 'datetime',
            'booth_last_billed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (Campaign $campaign): void {
            if (! $campaign->exists || $campaign->isDirty('slug')) {
                $campaign->slug = $campaign->uniqueSlug();
            }
        });
    }

    private function uniqueSlug(): string
    {
        $source = filled($this->slug) ? $this->slug : $this->name;
        $base = rtrim(Str::substr(Str::slug((string) $source), 0, 240), '-') ?: 'campaign';
        $candidate = $base;
        $suffix = 2;

        while (self::query()
            ->where('slug', $candidate)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
