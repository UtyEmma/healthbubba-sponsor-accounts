<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\UpdatePaymentGatewaySettingsData;
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

    public function execute(User $user, UpdatePaymentGatewaySettingsData $data): PaymentGatewaySetting
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException;
        }

        $errors = [];

        foreach ($data->all() as $accountType => $gateway) {
            $issues = $this->configuration->issues($gateway);

            if ($issues !== []) {
                $errors["gateways.{$accountType}"] = $issues;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $setting = DB::transaction(function () use ($user, $data): PaymentGatewaySetting {
            $setting = PaymentGatewaySetting::query()
                ->whereKey(PaymentGatewaySetting::SINGLETON_ID)
                ->lockForUpdate()
                ->first();

            if (! $setting instanceof PaymentGatewaySetting) {
                $setting = new PaymentGatewaySetting;
                $setting->setAttribute($setting->getKeyName(), PaymentGatewaySetting::SINGLETON_ID);
            }

            $setting->fill([
                'active_gateway' => $data->individual,
                'individual_gateway' => $data->individual,
                'business_gateway' => $data->business,
                'institution_gateway' => $data->institution,
                'updated_by_user_id' => $user->getKey(),
            ])->save();

            return $setting->refresh();
        }, 3);

        $this->resolver->forget();

        return $setting;
    }
}
