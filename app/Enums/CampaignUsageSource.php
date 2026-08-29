<?php

namespace App\Enums;

enum CampaignUsageSource: string
{
    case Manual = 'manual';
    case Provider = 'provider';
    case Legacy = 'legacy';
}
