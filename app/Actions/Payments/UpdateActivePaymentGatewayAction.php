<?php

namespace App\Actions\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use App\Payments\ActivePaymentGatewayResolver;
use App\Payments\PaymentGatewayConfiguration;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateActivePaymentGatewayAction
{
    public function __construct(
        private PaymentGatewayConfiguration $configuration,
        private ActivePaymentGatewayResolver $resolver,
    ) {}

    public function execute(User $user, PaymentGatewayName $gateway): PaymentGatewaySetting
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException;
        }

        $issues = $this->configuration->issues($gateway);

        if ($issues !== []) {
            throw ValidationException::withMessages(['gateway' => $issues]);
        }

        $setting = DB::transaction(function () use ($user, $gateway): PaymentGatewaySetting {
            $setting = PaymentGatewaySetting::query()
                ->whereKey(PaymentGatewaySetting::SINGLETON_ID)
                ->lockForUpdate()
                ->first();

            if (! $setting instanceof PaymentGatewaySetting) {
                $setting = new PaymentGatewaySetting;
                $setting->setAttribute($setting->getKeyName(), PaymentGatewaySetting::SINGLETON_ID);
            }

            $setting->fill([
                'active_gateway' => $gateway,
                'updated_by_user_id' => $user->getKey(),
            ])->save();

            return $setting->refresh();
        }, 3);

        $this->resolver->forget();

        return $setting;
    }
}
