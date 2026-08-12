<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\Models\Workspace;

final class EmployeeIdGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function generate(Workspace $workspace): string
    {
        do {
            $identifier = 'EMP-';

            for ($index = 0; $index < 8; $index++) {
                $identifier .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while ($workspace->workspaceBeneficiaries()->where('employee_id', $identifier)->exists());

        return $identifier;
    }
}
