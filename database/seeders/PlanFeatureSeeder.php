<?php

namespace Database\Seeders;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\FeatureSlug;
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

                foreach ($planFeatures as $featureSlug => $pivot) {
                    $feature = $features->get($featureSlug);

                    if (! $feature instanceof Feature) {
                        throw new LogicException("Feature [{$featureSlug}] is missing from the catalog.");
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
            FeatureSlug::ON_DEMAND_CONSULTATIONS->value => $this->toggle(FeatureSlug::ON_DEMAND_CONSULTATIONS->label(), 'Start an eligible consultation immediately.', 1),
            FeatureSlug::SCHEDULED_APPOINTMENTS->value => $this->toggle(FeatureSlug::SCHEDULED_APPOINTMENTS->label(), 'Book eligible consultations for a later time.', 2),
            FeatureSlug::BENEFICIARIES_INCLUDED->value => $this->limit(FeatureSlug::BENEFICIARIES_INCLUDED->label(), 'Beneficiaries included before overage pricing applies.', 3),
            FeatureSlug::MAXIMUM_BENEFICIARIES->value => $this->limit(FeatureSlug::MAXIMUM_BENEFICIARIES->label(), 'Maximum beneficiaries allowed on an individual plan.', 4),
            FeatureSlug::GP_CONSULTATIONS->value => $this->limit(FeatureSlug::GP_CONSULTATIONS->label(), 'Shared or pooled general practitioner consultation allowance.', 5),
            FeatureSlug::SPECIALIST_CONSULTATIONS->value => $this->limit(FeatureSlug::SPECIALIST_CONSULTATIONS->label(), 'Shared or pooled specialist consultation allowance.', 6),
            FeatureSlug::FOLLOW_UP_TRACKING->value => $this->toggle(FeatureSlug::FOLLOW_UP_TRACKING->label(), 'Track recommended follow-up care after consultations.', 7),
            FeatureSlug::PRIORITY_SUPPORT->value => $this->toggle(FeatureSlug::PRIORITY_SUPPORT->label(), 'Receive prioritized customer support.', 8),
            FeatureSlug::DEDICATED_COORDINATOR->value => $this->toggle(FeatureSlug::DEDICATED_COORDINATOR->label(), 'Access a dedicated care coordinator.', 9),
            FeatureSlug::CHRONIC_DISEASE_MONITORING->value => $this->toggle(FeatureSlug::CHRONIC_DISEASE_MONITORING->label(), 'Ongoing monitoring for eligible chronic conditions.', 10),
            FeatureSlug::GP_CONSULTATIONS_PER_SEAT->value => $this->limit(FeatureSlug::GP_CONSULTATIONS_PER_SEAT->label(), 'Monthly GP consultation allowance for each employee seat.', 11),
            FeatureSlug::SPECIALIST_CONSULTATIONS_PER_SEAT->value => $this->limit(FeatureSlug::SPECIALIST_CONSULTATIONS_PER_SEAT->label(), 'Monthly specialist consultation allowance for each employee seat.', 12),
            FeatureSlug::EMPLOYEE_SEAT_MANAGEMENT->value => $this->toggle(FeatureSlug::EMPLOYEE_SEAT_MANAGEMENT->label(), 'Provision and manage isolated employee coverage seats.', 13),
            FeatureSlug::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => $this->toggle(FeatureSlug::BULK_HR_UPLOAD_AND_LIST_EXPORT->label(), 'Bulk import employees and export workforce lists.', 14),
            FeatureSlug::ACTIVITY_AND_COVERAGE_LOGS->value => $this->toggle(FeatureSlug::ACTIVITY_AND_COVERAGE_LOGS->label(), 'Review activity and coverage utilization history.', 15),
            FeatureSlug::LAB_TEST_AND_MEDICATION_DISCOUNTS->value => $this->toggle(FeatureSlug::LAB_TEST_AND_MEDICATION_DISCOUNTS->label(), 'Receive eligible laboratory and medication discounts.', 16),
            FeatureSlug::ENHANCED_ANALYTICS_SUITE->value => $this->toggle(FeatureSlug::ENHANCED_ANALYTICS_SUITE->label(), 'Access enhanced utilization and coverage analytics.', 17),
            FeatureSlug::SHARED_COVERAGE_POOL->value => $this->toggle(FeatureSlug::SHARED_COVERAGE_POOL->label(), 'Share purchased consultation units across beneficiaries.', 18),
            FeatureSlug::COVERAGE_TOP_UPS->value => $this->toggle(FeatureSlug::COVERAGE_TOP_UPS->label(), 'Purchase additional consultation units during a coverage term.', 19),
            FeatureSlug::BULK_BENEFICIARY_UPLOAD->value => $this->toggle(FeatureSlug::BULK_BENEFICIARY_UPLOAD->label(), 'Bulk import institutional beneficiaries.', 20),
            FeatureSlug::ENROLLMENT_CODES->value => $this->toggle(FeatureSlug::ENROLLMENT_CODES->label(), 'Create and manage beneficiary enrollment codes.', 21),
            FeatureSlug::COVERAGE_RULES->value => $this->toggle(FeatureSlug::COVERAGE_RULES->label(), 'Configure institutional coverage allocation rules.', 22),
            FeatureSlug::COVERAGE_REPORTING->value => $this->toggle(FeatureSlug::COVERAGE_REPORTING->label(), 'Download institutional coverage and utilization reports.', 23),
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
                    FeatureSlug::ON_DEMAND_CONSULTATIONS->value => [],
                    FeatureSlug::SCHEDULED_APPOINTMENTS->value => [],
                    FeatureSlug::BENEFICIARIES_INCLUDED->value => ['value' => '3', 'unit_price' => '7000'],
                    FeatureSlug::MAXIMUM_BENEFICIARIES->value => ['value' => '6'],
                    FeatureSlug::GP_CONSULTATIONS->value => ['value' => '5', ...$monthlyReset],
                    FeatureSlug::SPECIALIST_CONSULTATIONS->value => ['value' => '2', ...$monthlyReset],
                ],
            ),
            'individual-premium' => $this->plan(
                name: 'Premium Plan',
                description: 'Monthly coverage for large families.',
                accountType: AccountTypes::INDIVIDUAL,
                price: '33000.00',
                sortOrder: 2,
                features: [
                    FeatureSlug::ON_DEMAND_CONSULTATIONS->value => [],
                    FeatureSlug::SCHEDULED_APPOINTMENTS->value => [],
                    FeatureSlug::BENEFICIARIES_INCLUDED->value => ['value' => '6', 'unit_price' => '7000'],
                    FeatureSlug::MAXIMUM_BENEFICIARIES->value => ['value' => '12'],
                    FeatureSlug::GP_CONSULTATIONS->value => ['value' => '10', ...$monthlyReset],
                    FeatureSlug::SPECIALIST_CONSULTATIONS->value => ['value' => '3', ...$monthlyReset],
                    FeatureSlug::FOLLOW_UP_TRACKING->value => [],
                    FeatureSlug::PRIORITY_SUPPORT->value => [],
                ],
            ),
            'individual-coordinated-care' => $this->plan(
                name: 'Coordinated Care Plan',
                description: 'Monthly coordinated care for elderly beneficiaries and people managing chronic conditions.',
                accountType: AccountTypes::INDIVIDUAL,
                price: '59000.00',
                sortOrder: 3,
                features: [
                    FeatureSlug::ON_DEMAND_CONSULTATIONS->value => [],
                    FeatureSlug::SCHEDULED_APPOINTMENTS->value => [],
                    FeatureSlug::BENEFICIARIES_INCLUDED->value => ['value' => '2'],
                    FeatureSlug::MAXIMUM_BENEFICIARIES->value => ['value' => '2'],
                    FeatureSlug::GP_CONSULTATIONS->value => ['value' => '12', ...$monthlyReset],
                    FeatureSlug::SPECIALIST_CONSULTATIONS->value => ['value' => '4', ...$monthlyReset],
                    FeatureSlug::FOLLOW_UP_TRACKING->value => [],
                    FeatureSlug::PRIORITY_SUPPORT->value => [],
                    FeatureSlug::DEDICATED_COORDINATOR->value => [],
                    FeatureSlug::CHRONIC_DISEASE_MONITORING->value => [],
                ],
            ),
            'business-basic' => $this->plan(
                name: 'Business Basic',
                description: 'Monthly per-seat coverage for SMEs and corporate teams.',
                accountType: AccountTypes::BUSINESS,
                price: '5000.00',
                sortOrder: 1,
                features: [
                    FeatureSlug::GP_CONSULTATIONS_PER_SEAT->value => ['value' => '2', ...$monthlyReset],
                    FeatureSlug::ON_DEMAND_CONSULTATIONS->value => [],
                    FeatureSlug::SCHEDULED_APPOINTMENTS->value => [],
                    FeatureSlug::EMPLOYEE_SEAT_MANAGEMENT->value => [],
                    FeatureSlug::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => [],
                    FeatureSlug::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                ],
            ),
            'business-premium' => $this->plan(
                name: 'Business Premium',
                description: 'Monthly per-seat coverage for enterprises and logistics companies.',
                accountType: AccountTypes::BUSINESS,
                price: '10500.00',
                sortOrder: 2,
                features: [
                    FeatureSlug::GP_CONSULTATIONS_PER_SEAT->value => ['value' => '3', ...$monthlyReset],
                    FeatureSlug::SPECIALIST_CONSULTATIONS_PER_SEAT->value => ['value' => '1', ...$monthlyReset],
                    FeatureSlug::ON_DEMAND_CONSULTATIONS->value => [],
                    FeatureSlug::SCHEDULED_APPOINTMENTS->value => [],
                    FeatureSlug::EMPLOYEE_SEAT_MANAGEMENT->value => [],
                    FeatureSlug::BULK_HR_UPLOAD_AND_LIST_EXPORT->value => [],
                    FeatureSlug::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                    FeatureSlug::PRIORITY_SUPPORT->value => [],
                    FeatureSlug::LAB_TEST_AND_MEDICATION_DISCOUNTS->value => [],
                    FeatureSlug::ENHANCED_ANALYTICS_SUITE->value => [],
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
                    FeatureSlug::GP_CONSULTATIONS->value => ['value' => '2000', ...$annualReset],
                    FeatureSlug::SPECIALIST_CONSULTATIONS->value => ['value' => '500', ...$annualReset],
                    FeatureSlug::SHARED_COVERAGE_POOL->value => [],
                    FeatureSlug::COVERAGE_TOP_UPS->value => [],
                    FeatureSlug::BULK_BENEFICIARY_UPLOAD->value => [],
                    FeatureSlug::ENROLLMENT_CODES->value => [],
                    FeatureSlug::COVERAGE_RULES->value => [],
                    FeatureSlug::COVERAGE_REPORTING->value => [],
                    FeatureSlug::ACTIVITY_AND_COVERAGE_LOGS->value => [],
                    FeatureSlug::ENHANCED_ANALYTICS_SUITE->value => [],
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
