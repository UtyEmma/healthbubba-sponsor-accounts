<?php

namespace App\DTOs\Institutional;

final readonly class InstitutionalConsultationRow
{
    /** @param array{name: string, slug: string}|null $campaign */
    public function __construct(
        public int $id,
        public ?string $date,
        public string $beneficiary,
        public ?array $campaign,
        public string $type,
        public string $typeLabel,
        public string $status,
        public string $statusLabel,
        public string $paymentSource,
        public string $paymentSourceLabel,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'beneficiary' => $this->beneficiary,
            'campaign' => $this->campaign,
            'type' => $this->type,
            'typeLabel' => $this->typeLabel,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'paymentSource' => $this->paymentSource,
            'paymentSourceLabel' => $this->paymentSourceLabel,
        ];
    }
}
