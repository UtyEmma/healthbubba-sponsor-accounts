<?php

namespace App\DTOs\MedicalAccess;

use App\Enums\MedicalAccess\MedicalAccessDataType;

final readonly class CreateMedicalAccessRequestData
{
    public function __construct(
        public string $beneficiaryPublicId,
        public MedicalAccessDataType $dataType,
        public ?string $reason,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            beneficiaryPublicId: (string) $data['beneficiary_public_id'],
            dataType: MedicalAccessDataType::from((string) $data['data_type']),
            reason: filled($data['reason'] ?? null) ? trim((string) $data['reason']) : null,
        );
    }
}
