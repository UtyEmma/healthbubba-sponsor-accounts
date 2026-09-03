<?php

namespace App\Models;

use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Consultations\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $relatable_type
 * @property int $relatable_id
 * @property int|null $invited_by_user_id
 * @property int|null $beneficiary_id
 * @property string $public_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string|null $department
 * @property string|null $employee_id
 * @property WorkspaceBeneficiaryStatus $status
 * @property WorkspaceBeneficiarySource $source
 * @property int $invitation_version
 * @property Carbon $invited_at
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $revoked_at
 * @property-read Workspace $workspace
 * @property-read Workspace|Campaign $relatable
 * @property-read Beneficiary|null $beneficiary
 */
final class WorkspaceBeneficiary extends Model
{
    protected $connection = 'mysql';

    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'relatable_type',
        'relatable_id',
        'invited_by_user_id',
        'beneficiary_id',
        'public_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'community',
        'campaign_booth_id',
        'department',
        'employee_id',
        'status',
        'source',
        'invitation_version',
        'invited_at',
        'expires_at',
        'accepted_at',
        'declined_at',
        'cancelled_at',
        'suspended_at',
        'revoked_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => WorkspaceBeneficiaryStatus::Pending,
        'source' => WorkspaceBeneficiarySource::Manual,
        'invitation_version' => 1,
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return MorphTo<Model, $this> */
    public function relatable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'beneficiary_id');
    }

    /** @return BelongsTo<CampaignBooth, $this> */
    public function campaignBooth(): BelongsTo
    {
        return $this->belongsTo(CampaignBooth::class);
    }

    /** @return HasMany<MedicalAccessRequest, $this> */
    public function medicalAccessRequests(): HasMany
    {
        return $this->hasMany(MedicalAccessRequest::class);
    }

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * @param  Builder<WorkspaceBeneficiary>  $query
     * @return Builder<WorkspaceBeneficiary>
     */
    public function scopeConsumingCapacity(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('status', [
                WorkspaceBeneficiaryStatus::Active,
                WorkspaceBeneficiaryStatus::Suspended,
            ])
                ->orWhere(function (Builder $query): void {
                    $query->where('status', WorkspaceBeneficiaryStatus::Pending)
                        ->where('expires_at', '>', now());
                });
        });
    }

    public function isPending(): bool
    {
        return $this->status === WorkspaceBeneficiaryStatus::Pending;
    }

    public function isActive(): bool
    {
        return $this->status === WorkspaceBeneficiaryStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === WorkspaceBeneficiaryStatus::Suspended;
    }

    public function hasExpired(): bool
    {
        return $this->isPending() && $this->expires_at->isPast();
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'status' => WorkspaceBeneficiaryStatus::class,
            'source' => WorkspaceBeneficiarySource::class,
            'invitation_version' => 'integer',
            'invited_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
