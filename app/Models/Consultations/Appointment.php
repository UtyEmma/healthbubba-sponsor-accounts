<?php

namespace App\Models\Consultations;

use App\Enums\Appointments\AppointmentStatus;
use App\Models\Beneficiary;
use App\Models\Doctor;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $appointment_id
 * @property int $patient_id
 * @property int|null $doctor_id
 * @property string|null $sponsor_id
 * @property Carbon|null $date
 * @property string|null $time
 * @property AppointmentStatus $status
 * @property Carbon|null $created_at
 * @property-read Beneficiary|null $patient
 * @property-read Doctor|null $doctor
 */
final class Appointment extends Model
{
    protected $connection = 'main_sql';

    protected $table = 'appointments';

    protected $primaryKey = 'appointment_id';

    protected $guarded = ['*'];

    /** @return BelongsTo<Beneficiary, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'patient_id');
    }

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSponsoredBy(Builder $query, Workspace $workspace): Builder
    {
        return $query->where('sponsor_id', (string) $workspace->getKey());
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'appointment_id' => 'integer',
            'patient_id' => 'integer',
            'doctor_id' => 'integer',
            'date' => 'date',
            'status' => AppointmentStatus::class,
            'created_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
