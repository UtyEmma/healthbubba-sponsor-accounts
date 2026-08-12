<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Subscription as SubscriptionifySubscription;

/**
 * @property int $id
 * @property string $subscribable_type
 * @property int $subscribable_id
 * @property int $plan_id
 * @property int|null $scheduled_plan_id
 * @property Carbon|null $scheduled_plan_change_at
 * @property int|null $payment_method_id
 * @property PaymentGatewayName|null $gateway
 * @property int $capacity_count
 * @property bool $auto_renew
 * @property Carbon|null $next_charge_at
 * @property int $renewal_attempts
 * @property Carbon|null $renewal_retry_at
 * @property Carbon|null $recurring_consent_at
 * @property SubscriptionStatus $status
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $renewed_at
 * @property-read Plan $plan
 * @property-read Plan|null $scheduledPlan
 * @property-read PaymentMethod|null $paymentMethod
 */
final class Subscription extends SubscriptionifySubscription
{
    /** @var list<string> */
    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'plan_id',
        'scheduled_plan_id',
        'scheduled_plan_change_at',
        'payment_method_id',
        'gateway',
        'capacity_count',
        'auto_renew',
        'next_charge_at',
        'renewal_attempts',
        'renewal_retry_at',
        'recurring_consent_at',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'renewed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'capacity_count' => 1,
        'auto_renew' => false,
        'renewal_attempts' => 0,
    ];

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function scheduledPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'scheduled_plan_id');
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'gateway' => PaymentGatewayName::class,
            'scheduled_plan_change_at' => 'datetime',
            'capacity_count' => 'integer',
            'auto_renew' => 'boolean',
            'next_charge_at' => 'datetime',
            'renewal_attempts' => 'integer',
            'renewal_retry_at' => 'datetime',
            'recurring_consent_at' => 'datetime',
        ];
    }
}
