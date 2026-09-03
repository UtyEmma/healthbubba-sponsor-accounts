<?php

namespace App\DTOs\WorkspaceBeneficiaries;

final readonly class EmployeeImportResult
{
    /** @param list<ImportRowError> $errors */
    public function __construct(
        public int $imported,
        public int $skipped,
        public array $errors,
        public ?string $publicId = null,
    ) {}

    /** @return array{id: string|null, processed: int, imported: int, skipped: int, errors: list<array{row: int, identifier: string|null, code: string, message: string, errors: list<string>}>} */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId,
            'processed' => $this->imported + $this->skipped,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => array_map(
                static fn (ImportRowError $error): array => [
                    'row' => $error->row,
                    'identifier' => $error->identifier,
                    'code' => $error->code,
                    'message' => $error->errors[0] ?? 'The row could not be imported.',
                    'errors' => $error->errors,
                ],
                $this->errors,
            ),
        ];
    }
}
