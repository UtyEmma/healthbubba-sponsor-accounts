<?php
namespace App\Repositories;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Revoltify\Subscriptionify\Models\Feature;

class FeaturesRepository {

    function getFeaturesByAccountType(?AccountTypes $accountType = null) {
        $accountType ??= Workspace::current()->type;

        return (new PlansRepository)->getPlansByAccountType($accountType)
                    ->flatMap(fn (Plan $plan): Collection => $plan->features)
                    ->unique('slug')
                    ->sortBy('sort_order')
                    ->values();
    }

    function getPlanFeatures(Plan $plan){
        return $this->getFeaturesByAccountType()
            ->filter(fn(Feature $feature) => !$feature->hasQuota())
            ->map(function (Feature $feature) use ($plan): array {
                $pivot = $feature?->limits;

                return [
                    'slug' => $feature->slug,
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'type' => $feature->type->value,
                    'included' => $plan->features()->where('id', $feature->id)->exists(),
                    'value' => $pivot->getValue(),
                    'unitPrice' => $pivot->getUnitPrice()
                ];
            })
            ->sortBy('included', SORT_REGULAR, true)
            ->all();
    }

    function getPlanQuotas(Plan $plan) {
        return $this->getFeaturesByAccountType()
                    ->filter(fn(Feature $feature) => $feature->hasQuota())
                    ->map(function (Feature $feature) use ($plan): array { 
                        $planFeature = $plan->features()->where('id', $feature->id)->first();
                        $limit = $planFeature?->limits?->value;
                        
                        return [
                            'name' => $feature->name,
                            'slug' => $feature->slug,
                            'quota' => $limit,
                            'description' => $planFeature ? match($plan->account_type) {
                                AccountTypes::BUSINESS => "{$limit} per employee / {$plan->billing_interval->value}",
                                default => "{$limit}"
                            } : 'Not Included'
                        ];
                    })->all();
    }

}