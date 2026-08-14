<?php

namespace App\Enums\Consultations;

enum ConsultationAllocationScope: string
{
    case Shared = 'shared';
    case PerEmployee = 'per_employee';

    public function label(): string
    {
        return match ($this) {
            self::Shared => 'Shared workspace pool',
            self::PerEmployee => 'Employee allocation',
        };
    }
}
