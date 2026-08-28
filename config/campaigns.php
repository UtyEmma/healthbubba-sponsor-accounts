<?php

return [
    'default_gp_fee' => (string) env('CAMPAIGN_DEFAULT_GP_FEE', '1000.00'),
    'default_specialist_fee' => (string) env('CAMPAIGN_DEFAULT_SPECIALIST_FEE', '5000.00'),
    'booth_setup_fee' => (string) env('CAMPAIGN_BOOTH_SETUP_FEE', '500000.00'),
    'booth_monthly_fee' => (string) env('CAMPAIGN_BOOTH_MONTHLY_FEE', '250000.00'),
];
