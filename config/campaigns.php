<?php

return [
    'default_scheduled_consultation_fee' => (string) env('CAMPAIGN_DEFAULT_SCHEDULED_CONSULTATION_FEE', '1000.00'),
    'default_instant_consultation_fee' => (string) env('CAMPAIGN_DEFAULT_INSTANT_CONSULTATION_FEE', '5000.00'),
    'booth_setup_fee' => (string) env('CAMPAIGN_BOOTH_SETUP_FEE', '500000.00'),
    'booth_monthly_fee' => (string) env('CAMPAIGN_BOOTH_MONTHLY_FEE', '250000.00'),
    'booth_billing_grace_days' => (int) env('CAMPAIGN_BOOTH_BILLING_GRACE_DAYS', 7),
];
