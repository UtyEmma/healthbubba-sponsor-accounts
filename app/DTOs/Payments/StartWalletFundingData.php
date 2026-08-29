<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\User;
use App\Models\Workspace;
use App\ValueObjects\Money;

final readonly class StartWalletFundingData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Money $amount,
        public string $callbackUrl,
        public ?PaymentGatewayName $gateway = null,
        /** @var list<string>|null */
        public ?array $channels = null,
        public ?string $fundingMethod = null,
    ) {}
}
