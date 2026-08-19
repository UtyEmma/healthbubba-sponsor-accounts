<?php

namespace App\DTOs\Campaigns;

use App\Enums\Consultations\ConsultationType;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;

final readonly class PurchaseConsultationQuotaData
{
    public function __construct(
        public Workspace $workspace,
        public Campaign $campaign,
        public User $user,
        public ConsultationType $consultationType,
        public int $quantity,
    ) {}
}
