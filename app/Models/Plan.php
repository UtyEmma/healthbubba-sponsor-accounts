<?php

namespace App\Models;

use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Revoltify\Subscriptionify\Models\Plan as SubscriptionifyPlan;

/**
 * @property AccountTypes $account_type
 * @property numeric-string $price
 * @property int|null $included_seats
 * @property numeric-string|null $additional_seat_price
 * @property bool $allows_capacity_purchases
 * @property bool $is_free
 * @property bool $is_active
 * @property int $trial_days
 * @property int $billing_period
 * @property Interval $billing_interval
 * @property int $grace_days
 * @property int $sort_order
 */
class Plan extends SubscriptionifyPlan
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'account_type',
        'price',
        'included_seats',
        'additional_seat_price',
        'allows_capacity_purchases',
        'is_free',
        'is_active',
        'trial_days',
        'billing_period',
        'billing_interval',
        'grace_days',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'price' => 0,
        'is_free' => false,
        'is_active' => true,
        'trial_days' => 0,
        'billing_period' => 1,
        'billing_interval' => 'month',
        'grace_days' => 0,
        'sort_order' => 0,
        'allows_capacity_purchases' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $plan): void {
            if ($plan->account_type === AccountTypes::INSTITUTION) {
                $plan->allows_capacity_purchases = false;
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function forAccountType(Builder $query, AccountTypes $accountType): Builder
    {
        return $query->where('account_type', $accountType);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'account_type' => AccountTypes::class,
            'price' => 'decimal:2',
            'included_seats' => 'integer',
            'additional_seat_price' => 'decimal:2',
            'allows_capacity_purchases' => 'boolean',
        ];
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /** @return BelongsToMany<Feature, $this, FeaturePlan> */
    public function features(): BelongsToMany
    {
        $table = config()->string('subscriptionify.tables.feature_plan', 'feature_plan');

        /** @var class-string<Feature> $model */
        $model = config('subscriptionify.models.feature', Feature::class);

        $relation = $this->belongsToMany($model, $table)
            ->using(FeaturePlan::class)
            ->withPivot(['value', 'unit_price', 'reset_period', 'reset_interval']);

        $this->useLimitsAccessor($relation);

        return $relation;
    }

    /** @param BelongsToMany<Feature, $this, FeaturePlan> $relation */
    private function useLimitsAccessor(BelongsToMany $relation): void
    {
        $relation->as('limits');
    }
}
