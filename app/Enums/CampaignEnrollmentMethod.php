<?php

namespace App\Enums;

enum CampaignEnrollmentMethod: string
{
    case Upload = 'upload';
    case Manual = 'manual';
}
