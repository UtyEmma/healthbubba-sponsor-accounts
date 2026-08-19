<?php

namespace App\Models;

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
 * @property string|null $country
 * @property string|null $city
 * @property string|null $state
 * @property string|null $location
 * @property string|null $target_audience
 * @property int $beneficiary_limit
 * @property bool $booth_required
 * @property string|null $gp_fee
 * @property string|null $specialist_fee
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Collection<int, WorkspaceBeneficiary> $beneficiaries
 * @property-read Collection<int, CampaignConsultationQuota> $consultationQuotas
 */
final class Campaign extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'beneficiary_limit' => 100,
    ];

    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'location',
        'state',
        'city',
        'country',
        'target_audience',
        'beneficiary_limit',
        'booth_required',
        'gp_fee',
        'specialist_fee',
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lifecycleStatus(): CampaignStatus
    {
        $today = CarbonImmutable::today();

        if ($this->end_date?->isBefore($today) === true) {
            return CampaignStatus::COMPLETED;
        }

        if ($this->start_date?->isAfter($today) === true) {
            return CampaignStatus::PENDING;
        }

        return CampaignStatus::IN_PROGRESS;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'booth_required' => 'boolean',
            'beneficiary_limit' => 'integer',
            'gp_fee' => 'decimal:2',
            'specialist_fee' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
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
