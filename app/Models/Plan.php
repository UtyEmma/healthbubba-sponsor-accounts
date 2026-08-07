<?php

namespace App\Models;

use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Revoltify\Subscriptionify\Models\Plan as SubscriptionifyPlan;

/**
 * @property AccountTypes $account_type
 * @property numeric-string $price
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
    ];

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function forAccountType(Builder $query, AccountTypes $accountType): Builder {
        return $query->where('account_type', $accountType);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array {
        return [
            ...parent::casts(),
            'account_type' => AccountTypes::class,
            'price' => 'decimal:2',
        ];
    }

    /** @return BelongsToMany<Feature, $this, FeaturePlan> */
    public function features(): BelongsToMany
    {
        $table = config()->string('subscriptionify.tables.feature_plan', 'feature_plan');

        /** @var class-string<Feature> $model */
        $model = config('subscriptionify.models.feature', Feature::class);

        return $this->belongsToMany($model, $table)
                    ->using(FeaturePlan::class)
                    ->as('limits')
                    ->withPivot(['value', 'unit_price', 'reset_period', 'reset_interval']);
    }
}
