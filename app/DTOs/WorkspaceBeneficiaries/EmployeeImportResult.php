<?php

namespace App\DTOs\WorkspaceBeneficiaries;

final readonly class EmployeeImportResult
{
    /** @param list<ImportRowError> $errors */
    public function __construct(public int $imported, public int $skipped, public array $errors) {}

    /** @return array{imported: int, skipped: int, errors: list<array{row: int, errors: list<string>}>} */
    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => array_map(
                static fn (ImportRowError $error): array => ['row' => $error->row, 'errors' => $error->errors],
                $this->errors,
            ),
        ];
    }
}
