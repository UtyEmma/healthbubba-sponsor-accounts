<?php

namespace App\Models;

use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property IndividualPlanBillingCycle $billing_cycle
 * @property numeric-string $price
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Plan $plan
 */
final class PlanIntervalPrice extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'price',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'billing_cycle' => IndividualPlanBillingCycle::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
