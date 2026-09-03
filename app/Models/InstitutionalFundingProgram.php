<?php

namespace App\Models;

use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property InstitutionalCoverageType $coverage_type
 * @property int $gp_limit_per_beneficiary
 * @property int $specialist_limit_per_beneficiary
 * @property int $daily_consultation_limit
 * @property InstitutionalCoverageExpiry $expiry_cadence
 * @property InstitutionalPaymentPreference $payment_preference
 * @property-read Workspace $workspace
 */
final class InstitutionalFundingProgram extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'coverage_type' => InstitutionalCoverageType::SharedPool->value,
        'gp_limit_per_beneficiary' => 4,
        'specialist_limit_per_beneficiary' => 2,
        'daily_consultation_limit' => 1,
        'expiry_cadence' => InstitutionalCoverageExpiry::Annual->value,
        'payment_preference' => InstitutionalPaymentPreference::UserChoice->value,
    ];

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'name', 'starts_on', 'ends_on', 'coverage_type',
        'gp_limit_per_beneficiary', 'specialist_limit_per_beneficiary',
        'daily_consultation_limit', 'expiry_cadence', 'payment_preference',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'coverage_type' => InstitutionalCoverageType::class,
            'gp_limit_per_beneficiary' => 'integer',
            'specialist_limit_per_beneficiary' => 'integer',
            'daily_consultation_limit' => 'integer',
            'expiry_cadence' => InstitutionalCoverageExpiry::class,
            'payment_preference' => InstitutionalPaymentPreference::class,
        ];
    }
}
