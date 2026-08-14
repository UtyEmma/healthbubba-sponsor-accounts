<?php

namespace App\Models\Consultations;

use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Models\Plan;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $workspace_id
 * @property int $workspace_beneficiary_id
 * @property int|null $plan_id
 * @property int $beneficiary_id
 * @property int $doctor_id
 * @property int|null $appointment_id
 * @property ConsultationType $consultation_type
 * @property string $feature_slug
 * @property ConsultationReservationStatus $status
 * @property ConsultationAllocationScope $allocation_scope
 * @property string $plan_name
 * @property int|null $allocation_limit
 * @property Carbon $allocation_period_start
 * @property Carbon $allocation_period_end
 * @property Carbon $reserved_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 */
final class Consultation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'workspace_id',
        'workspace_beneficiary_id',
        'plan_id',
        'beneficiary_id',
        'doctor_id',
        'appointment_id',
        'consultation_type',
        'feature_slug',
        'status',
        'allocation_scope',
        'plan_name',
        'allocation_limit',
        'allocation_period_start',
        'allocation_period_end',
        'reserved_at',
        'confirmed_at',
        'cancelled_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ConsultationReservationStatus::Reserved,
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

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

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'beneficiary_id' => 'integer',
            'doctor_id' => 'integer',
            'appointment_id' => 'integer',
            'consultation_type' => ConsultationType::class,
            'status' => ConsultationReservationStatus::class,
            'allocation_scope' => ConsultationAllocationScope::class,
            'allocation_limit' => 'integer',
            'allocation_period_start' => 'datetime',
            'allocation_period_end' => 'datetime',
            'reserved_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
