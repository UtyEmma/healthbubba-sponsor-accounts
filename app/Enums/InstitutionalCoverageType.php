<?php

namespace App\Enums;

enum InstitutionalCoverageType: string
{
    case SharedPool = 'shared_pool';
    case PerBeneficiary = 'per_beneficiary';

    public function label(): string
    {
        return match ($this) {
            self::SharedPool => 'Shared Coverage Pool',
            self::PerBeneficiary => 'Per-Beneficiary Coverage',
        };
    }
}
