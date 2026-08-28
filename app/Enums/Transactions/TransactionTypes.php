<?php

namespace App\Enums\Transactions;

enum TransactionTypes: string
{
    case TOPUP = 'topup';
    case SUBSCRIPTION = 'subscription';
    case CAPACITY_PURCHASE = 'capacity_purchase';
    case PLAN_CHANGE = 'plan_change';
    case CONSULTATION_QUOTA = 'consultation_quota';
    case CAMPAIGN_ALLOCATION = 'campaign_allocation';
    case CAMPAIGN_REFUND = 'campaign_refund';
    case CAMPAIGN_BOOTH_SETUP = 'campaign_booth_setup';
    case CAMPAIGN_BOOTH_SERVICE = 'campaign_booth_service';
}
