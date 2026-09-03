<?php

namespace App\Enums;

enum InstitutionalCoverageExpiry: string
{
    case Annual = 'annual';

    public function label(): string
    {
        return 'Annual';
    }
}
