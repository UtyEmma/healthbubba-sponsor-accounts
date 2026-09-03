<?php

namespace App\Enums;

enum InstitutionalReportType: string
{
    case Beneficiaries = 'beneficiaries';
    case Coverage = 'coverage';
    case Utilization = 'utilization';

    public function label(): string
    {
        return match ($this) {
            self::Beneficiaries => 'Beneficiary Report',
            self::Coverage => 'Coverage Report',
            self::Utilization => 'Utilization Report',
        };
    }
}
