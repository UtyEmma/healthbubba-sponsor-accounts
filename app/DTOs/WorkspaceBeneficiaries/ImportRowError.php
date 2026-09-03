<?php

namespace App\DTOs\WorkspaceBeneficiaries;

final readonly class ImportRowError
{
    /** @param list<string> $errors */
    public function __construct(
        public int $row,
        public array $errors,
        public ?string $identifier = null,
        public string $code = 'INVALID_ROW',
    ) {}
}
