<?php

namespace App\Models;

use App\Enums\MedicalAccess\MedicalAccessDataType;
use App\Enums\MedicalAccess\MedicalAccessRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $workspace_id
 * @property int $workspace_beneficiary_id
 * @property int|null $requested_by_user_id
 * @property MedicalAccessDataType $data_type
 * @property string|null $reason
 * @property MedicalAccessRequestStatus $status
 * @property Carbon $requested_at
 * @property Carbon $review_expires_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $denied_at
 * @property Carbon|null $access_expires_at
 * @property-read Workspace $workspace
 * @property-read WorkspaceBeneficiary $workspaceBeneficiary
 * @property-read User|null $requester
 */
final class MedicalAccessRequest extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'workspace_id',
        'workspace_beneficiary_id',
        'requested_by_user_id',
        'data_type',
        'reason',
        'status',
        'requested_at',
        'review_expires_at',
        'approved_at',
        'denied_at',
        'access_expires_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => MedicalAccessRequestStatus::Pending,
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<WorkspaceBeneficiary, $this> */
    public function workspaceBeneficiary(): BelongsTo
    {
        return $this->belongsTo(WorkspaceBeneficiary::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @param  Builder<MedicalAccessRequest>  $query
     * @return Builder<MedicalAccessRequest>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where('status', MedicalAccessRequestStatus::Pending)
                    ->where('review_expires_at', '>', now());
            })->orWhere(function (Builder $query): void {
                $query->where('status', MedicalAccessRequestStatus::Approved)
                    ->where('access_expires_at', '>', now());
            });
        });
    }

    public function isPending(): bool
    {
        return $this->status === MedicalAccessRequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === MedicalAccessRequestStatus::Approved;
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'data_type' => MedicalAccessDataType::class,
            'status' => MedicalAccessRequestStatus::class,
            'requested_at' => 'datetime',
            'review_expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'denied_at' => 'datetime',
            'access_expires_at' => 'datetime',
        ];
    }
}
