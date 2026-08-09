<?php

namespace Database\Seeders;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Models\Feature;

class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $features = collect($this->featureDefinitions())
                ->mapWithKeys(function (array $attributes, string $slug): array {
                    $feature = Feature::query()->updateOrCreate(
                        ['slug' => $slug],
                        $attributes,
                    );

                    return [$slug => $feature];
                });

            foreach ($this->planDefinitions() as $slug => $definition) {
                $planFeatures = $definition['features'];
                unset($definition['features']);

                $plan = Plan::query()->updateOrCreate(
                    ['slug' => $slug],
                    $definition,
                );

                $pivots = [];

                foreach ($planFeatures as $Features => $pivot) {
                    $feature = $features->get($Features);

                    if (! $feature instanceof Feature) {
                        throw new LogicException("Feature [{$Features}] is missing from the catalog.");
                    }

                    $pivots[$feature->getKey()] = [
                        'value' => '1',
                        'unit_price' => '0',
                        'reset_period' => null,
                        'reset_interval' => null,
                        ...$pivot,
                    ];
                }

                $plan->features()->sync($pivots);
            }
        });
    }

    /**
     * @return array<string, array{name: string, description: string, type: FeatureType, sort_order: int}>
     */
    private function featureDefinitions(): array
    {
        return [
            Features::ON_DEMAND_CONSULTATIONS->value => $this->toggle(Features::ON_DEMAND_CONSULTATIONS->label(), 'Start an eligible consultation immediately.', 1),
            Features::SCHEDULED_APPOINTMENTS->value => $this->toggle(Features::SCHEDULED_APPOINTMENTS->label(), 'Book eligible consultations for a later time.', 2),
            Features::BENEFICIARIES_INCLUDED->value => $this->limit(Features::BENEFICIARIES_INCLUDED->label(), 'Beneficiaries included before overage pricing applies.', 3),
            Features::MAXIMUM_BENEFICIARIES->value => $this->limit(Features::MAXIMUM_BENEFICIARIES->label(), 'Maximum beneficiaries allowed on an individual plan.', 4),
            Features::GP_CONSULTATIONS->value => $this->limit(Features::GP_CONSULTATIONS->label(), 'Shared or pooled general practitioner consultation allowance.', 5),
            Features::SPECIALIST_CONSULTATIONS->value => $this->limit(Features::SPECIALIST_CONSULTATIONS->label(), 'Shared or pooled specialist consultation allowance.', 6),
            Features::FOLLOW_UP_TRACKING->value => $this->toggle(Features::FOLLOW_UP_TRACKING->label(), 'Track recommended follow-up care after consultations.', 7),
            Features::PRIORITY_SUPPORT->value => $this->toggle(Features::PRIORITY_SUPPORT->label(), 'Receive prioritized customer support.', 8),
            Features::DEDICATED_COORDINATOR->value => $this->toggle(Features::DEDICATED_COORDINATOR->label(), 'Access a dedicated care coordinator.', 9),
            Features::CHRONIC_DISEASE_MONITORING->value => $this->toggle(Features::CHRONIC_DISEASE_MONITORING->label(), 'Ongoing monitoring for eligible chronic conditions.', 10),
            Features::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => $this->toggle(Features::BULK_HR_UPLOAD_AND_LIST_EXPORT->label(), 'Bulk import employees and export workforce lists.', 14),
            Features::ACTIVITY_AND_COVERAGE_LOGS->value => $this->toggle(Features::ACTIVITY_AND_COVERAGE_LOGS->label(), 'Review activity and coverage utilization history.', 15),
            Features::LAB_TEST_AND_MEDICATION_DISCOUNTS->value => $this->toggle(Features::LAB_TEST_AND_MEDICATION_DISCOUNTS->label(), 'Receive eligible laboratory and medication discounts.', 16),
            Features::ENHANCED_ANALYTICS_SUITE->value => $this->toggle(Features::ENHANCED_ANALYTICS_SUITE->label(), 'Access enhanced utilization and coverage analytics.', 17),
            Features::SHARED_COVERAGE_POOL->value => $this->toggle(Features::SHARED_COVERAGE_POOL->label(), 'Share purchased consultation units across beneficiaries.', 18),
            Features::COVERAGE_TOP_UPS->value => $this->toggle(Features::COVERAGE_TOP_UPS->label(), 'Purchase additional consultation units during a coverage term.', 19),
            Features::BULK_BENEFICIARY_UPLOAD->value => $this->toggle(Features::BULK_BENEFICIARY_UPLOAD->label(), 'Bulk import institutional beneficiaries.', 20),
            Features::ENROLLMENT_CODES->value => $this->toggle(Features::ENROLLMENT_CODES->label(), 'Create and manage beneficiary enrollment codes.', 21),
            Features::COVERAGE_RULES->value => $this->toggle(Features::COVERAGE_RULES->label(), 'Configure institutional coverage allocation rules.', 22),
            Features::COVERAGE_REPORTING->value => $this->toggle(Features::COVERAGE_REPORTING->label(), 'Download institutional coverage and utilization reports.', 23),
        ];
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     account_type: AccountTypes,
     *     price: string,
     *     is_free: bool,
     *     is_active: bool,
     *     trial_days: int,
     *     billing_period: int,
     *     billing_interval: Interval,
     *     grace_days: int,
     *     sort_order: int,
     *     features: array<string, array<string, int|string|null>>
     * }>
     */
    private function planDefinitions(): array
    {
        $monthlyReset = [
            'reset_period' => 1,
            'reset_interval' => Interval::Month->value,
        ];

        $annualReset = [
            'reset_period' => 1,
            'reset_interval' => Interval::Year->value,
        ];

        return [
            'individual-basic' => $this->plan(
                name: 'Basic Plan',
                description: 'Monthly coverage for small families.',
                accountType: AccountTypes::INDIVIDUAL,
                price: '20000.00',
                sortOrder: 1,
                features: [
                    Features::ON_DEMAND_CONSULTATIONS->value => [],
                    Features::SCHEDULED_APPOINTMENTS->value => [],
                    Features::BENEFICIARIES_INCLUDED->value => ['value' => '3', 'unit_price' => '7000'],
                    Features::MAXIMUM_BENEFICIARIES->value => ['value' => '6'],
                    Features::GP_CONSULTATIONS->value => ['value' => '5', ...$monthlyReset],
                    Features::SPECIALIST_CONSULTATIONS->value => ['value' => '2', ...$monthlyReset],
                ],
            ),
            'individual-premium' => $this->plan(
                name: 'Premium Plan',
                description: 'Monthly coverage for large families.',
                accountType: AccountTypes::INDIVIDUAL,
                price: '33000.00',
                sortOrder: 2,
                features: [
                    Features::ON_DEMAND_CONSULTATIONS->value => [],
                    Features::SCHEDULED_APPOINTMENTS->value => [],
                    Features::BENEFICIARIES_INCLUDED->value => ['value' => '6', 'unit_price' => '7000'],
                    Features::MAXIMUM_BENEFICIARIES->value => ['value' => '12'],
                    Features::GP_CONSULTATIONS->value => ['value' => '10', ...$monthlyReset],
                    Features::SPECIALIST_CONSULTATIONS->value => ['value' => '3', ...$monthlyReset],
                    Features::FOLLOW_UP_TRACKING->value => [],
                    Features::PRIORITY_SUPPORT->value => [],
                ],
            ),
            'individual-coordinated-care' => $this->plan(
                name: 'Coordinated Care Plan',
                description: 'Monthly coordinated care for elderly beneficiaries and people managing chronic conditions.',
                accountType: AccountTypes::INDIVIDUAL,
                price: '59000.00',
                sortOrder: 3,
                features: [
                    Features::ON_DEMAND_CONSULTATIONS->value => [],
                    Features::SCHEDULED_APPOINTMENTS->value => [],
                    Features::BENEFICIARIES_INCLUDED->value => ['value' => '2'],
                    Features::MAXIMUM_BENEFICIARIES->value => ['value' => '2'],
                    Features::GP_CONSULTATIONS->value => ['value' => '12', ...$monthlyReset],
                    Features::SPECIALIST_CONSULTATIONS->value => ['value' => '4', ...$monthlyReset],
                    Features::FOLLOW_UP_TRACKING->value => [],
                    Features::PRIORITY_SUPPORT->value => [],
                    Features::DEDICATED_COORDINATOR->value => [],
                    Features::CHRONIC_DISEASE_MONITORING->value => [],
                ],
            ),
            'business-basic' => $this->plan(
                name: 'Business Basic',
                description: 'Monthly per-seat coverage for SMEs and corporate teams.',
                accountType: AccountTypes::BUSINESS,
                price: '5000.00',
                sortOrder: 1,
                features: [
                    Features::GP_CONSULTATIONS_PER_SEAT->value => ['value' => '2', ...$monthlyReset],
                    Features::ON_DEMAND_CONSULTATIONS->value => [],
                    Features::SCHEDULED_APPOINTMENTS->value => [],
                    Features::EMPLOYEE_SEAT_MANAGEMENT->value => [],
                    Features::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => [],
                    Features::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                ],
            ),
            'business-premium' => $this->plan(
                name: 'Business Premium',
                description: 'Monthly per-seat coverage for enterprises and logistics companies.',
                accountType: AccountTypes::BUSINESS,
                price: '10500.00',
                sortOrder: 2,
                features: [
                    Features::GP_CONSULTATIONS->value => ['value' => '3', ...$monthlyReset],
                    Features::SPECIALIST_CONSULTATIONS->value => ['value' => '1', ...$monthlyReset],
                    Features::ON_DEMAND_CONSULTATIONS->value => [],
                    Features::SCHEDULED_APPOINTMENTS->value => [],
                    Features::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => [],
                    Features::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                    Features::PRIORITY_SUPPORT->value => [],
                    Features::LAB_TEST_AND_MEDICATION_DISCOUNTS->value => [],
                    Features::ENHANCED_ANALYTICS_SUITE->value => [],
                ],
            ),
            'institution-community-health-program-2026' => $this->plan(
                name: 'Community Health Program 2026',
                description: 'Annual shared coverage pool for institutional beneficiaries.',
                accountType: AccountTypes::INSTITUTION,
                price: '25000000.00',
                sortOrder: 1,
                billingInterval: Interval::Year,
                features: [
                    Features::GP_CONSULTATIONS->value => ['value' => '2000', ...$annualReset],
                    Features::SPECIALIST_CONSULTATIONS->value => ['value' => '500', ...$annualReset],
                    Features::SHARED_COVERAGE_POOL->value => [],
                    Features::COVERAGE_TOP_UPS->value => [],
                    Features::BULK_BENEFICIARY_UPLOAD->value => [],
                    Features::ENROLLMENT_CODES->value => [],
                    Features::COVERAGE_RULES->value => [],
                    Features::COVERAGE_REPORTING->value => [],
                    Features::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                    Features::ENHANCED_ANALYTICS_SUITE->value => [],
                ],
            ),
        ];
    }

    /**
     * @return array{name: string, description: string, type: FeatureType, sort_order: int}
     */
    private function toggle(string $name, string $description, int $sortOrder): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'type' => FeatureType::Toggle,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @return array{name: string, description: string, type: FeatureType, sort_order: int}
     */
    private function limit(string $name, string $description, int $sortOrder): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'type' => FeatureType::Limit,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @param  array<string, array<string, int|string|null>>  $features
     * @return array{
     *     name: string,
     *     description: string,
     *     account_type: AccountTypes,
     *     price: string,
     *     is_free: bool,
     *     is_active: bool,
     *     trial_days: int,
     *     billing_period: int,
     *     billing_interval: Interval,
     *     grace_days: int,
     *     sort_order: int,
     *     features: array<string, array<string, int|string|null>>
     * }
     */
    private function plan(
        string $name,
        string $description,
        AccountTypes $accountType,
        string $price,
        int $sortOrder,
        array $features,
        Interval $billingInterval = Interval::Month,
    ): array {
        return [
            'name' => $name,
            'description' => $description,
            'account_type' => $accountType,
            'price' => $price,
            'is_free' => false,
            'is_active' => true,
            'trial_days' => 0,
            'billing_period' => 1,
            'billing_interval' => $billingInterval,
            'grace_days' => 0,
            'sort_order' => $sortOrder,
            'features' => $features,
        ];
    }
}
