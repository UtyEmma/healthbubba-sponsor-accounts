<?php

namespace App\DTOs\Dashboard;

final readonly class DepartmentUtilization
{
    public function __construct(
        public string $department,
        public int $gp,
        public int $specialist,
    ) {}
}
