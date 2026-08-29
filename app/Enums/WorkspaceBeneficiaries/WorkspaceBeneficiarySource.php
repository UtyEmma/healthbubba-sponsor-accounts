<?php

namespace App\Enums\WorkspaceBeneficiaries;

enum WorkspaceBeneficiarySource: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Booth = 'booth';
    case EnrollmentCode = 'enrollment_code';
}
