<?php

namespace App\Enums\Subscriptions;

enum Features: string
{
    case ON_DEMAND_CONSULTATIONS = 'on-demand-consultations';
    case SCHEDULED_APPOINTMENTS = 'scheduled-appointments';
    case BENEFICIARIES_INCLUDED = 'beneficiaries-included';
    case MAXIMUM_BENEFICIARIES = 'maximum-beneficiaries';
    case GP_CONSULTATIONS = 'gp-consultations';
    case SPECIALIST_CONSULTATIONS = 'specialist-consultations';
    case FOLLOW_UP_TRACKING = 'follow-up-tracking';
    case PRIORITY_SUPPORT = 'priority-support';
    case DEDICATED_COORDINATOR = 'dedicated-coordinator';
    case CHRONIC_DISEASE_MONITORING = 'chronic-disease-monitoring';
    case GP_CONSULTATIONS_PER_SEAT = 'gp-consultations-per-seat';
    case SPECIALIST_CONSULTATIONS_PER_SEAT = 'specialist-consultations-per-seat';
    case EMPLOYEE_SEAT_MANAGEMENT = 'employee-seat-management';
    case BULK_HR_UPLOAD_AND_LIST_EXPORT = 'bulk-hr-upload-and-list-export';
    case ACTIVITY_AND_COVERAGE_LOGS = 'activity-and-coverage-logs';
    case LAB_TEST_AND_MEDICATION_DISCOUNTS = 'lab-test-and-medication-discounts';
    case ENHANCED_ANALYTICS_SUITE = 'enhanced-analytics-suite';
    case SHARED_COVERAGE_POOL = 'shared-coverage-pool';
    case COVERAGE_TOP_UPS = 'coverage-top-ups';
    case BULK_BENEFICIARY_UPLOAD = 'bulk-beneficiary-upload';
    case ENROLLMENT_CODES = 'enrollment-codes';
    case COVERAGE_RULES = 'coverage-rules';
    case COVERAGE_REPORTING = 'coverage-reporting';

    public function label(): string
    {
        return match ($this) {
            self::ON_DEMAND_CONSULTATIONS => 'On-Demand Consultations',
            self::SCHEDULED_APPOINTMENTS => 'Instant Consultations',
            self::BENEFICIARIES_INCLUDED => 'Included Beneficiaries',
            self::MAXIMUM_BENEFICIARIES => 'Maximum Beneficiaries',
            self::GP_CONSULTATIONS => 'Scheduled Consultations',
            self::SPECIALIST_CONSULTATIONS => 'Instant Consultations',
            self::FOLLOW_UP_TRACKING => 'Follow-Up Tracking',
            self::PRIORITY_SUPPORT => 'Priority Support',
            self::DEDICATED_COORDINATOR => 'Dedicated Coordinator',
            self::CHRONIC_DISEASE_MONITORING => 'Chronic Disease Monitoring',
            self::GP_CONSULTATIONS_PER_SEAT => 'Scheduled Consultations per seat',
            self::SPECIALIST_CONSULTATIONS_PER_SEAT => 'Instant Consultations per seat',
            self::EMPLOYEE_SEAT_MANAGEMENT => 'Employee Seat Management',
            self::BULK_HR_UPLOAD_AND_LIST_EXPORT => 'Bulk HR Upload and List Export',
            self::ACTIVITY_AND_COVERAGE_LOGS => 'Activity and Coverage Logs',
            self::LAB_TEST_AND_MEDICATION_DISCOUNTS => 'Lab Test and Medication Discounts',
            self::ENHANCED_ANALYTICS_SUITE => 'Enhanced Analytics Suite',
            self::SHARED_COVERAGE_POOL => 'Shared Coverage Pool',
            self::COVERAGE_TOP_UPS => 'Coverage Top-Ups',
            self::BULK_BENEFICIARY_UPLOAD => 'Bulk Beneficiary Upload',
            self::ENROLLMENT_CODES => 'Enrollment Codes',
            self::COVERAGE_RULES => 'Coverage Rules',
            self::COVERAGE_REPORTING => 'Coverage Reporting',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $feature): array => [$feature->value => $feature->label()])
            ->all();
    }
}
