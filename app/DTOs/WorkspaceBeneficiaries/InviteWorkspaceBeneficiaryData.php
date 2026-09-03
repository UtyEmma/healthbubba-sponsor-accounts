<?php

namespace App\DTOs\WorkspaceBeneficiaries;

use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;

final readonly class InviteWorkspaceBeneficiaryData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public ?string $department,
        public ?string $employeeId,
        public ?string $community,
        public WorkspaceBeneficiarySource $source = WorkspaceBeneficiarySource::Manual,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, WorkspaceBeneficiarySource $source = WorkspaceBeneficiarySource::Manual): self
    {
        return new self(
            firstName: trim((string) $data['first_name']),
            lastName: trim((string) $data['last_name']),
            email: mb_strtolower(trim((string) $data['email'])),
            phone: trim((string) $data['phone']),
            department: filled($data['department'] ?? null) ? trim((string) $data['department']) : null,
            employeeId: filled($data['employee_id'] ?? null) ? mb_strtoupper(trim((string) $data['employee_id'])) : null,
            community: filled($data['community'] ?? null) ? trim((string) $data['community']) : null,
            source: $source,
        );
    }
}
