<?php

namespace App\DTOs\Admin;

use App\ValueObjects\Money;

final readonly class RecordAdminWorkspaceFundingData
{
    public function __construct(
        public int $workspaceId,
        public Money $amount,
        public ?string $note = null,
        public ?string $externalReference = null,
    ) {}
}
