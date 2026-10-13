<?php

namespace App\Filament\Pages;

use App\Actions\Payments\UpdateActivePaymentGatewayAction;
use App\DTOs\Payments\UpdatePaymentGatewaySettingsData;
use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentGatewayName;
use App\Models\User;
use App\Payments\ActivePaymentGatewayResolver;
use App\Payments\PaymentGatewayConfiguration;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use UnitEnum;

final class PaymentGatewaySettings extends Page
{
    protected string $view = 'filament.pages.payment-gateway-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Payment Gateways';

    /** @var array<string, string> */
    public array $gateways = [];

    /** @var array<string, string> */
    public array $accountTypeOptions = [];

    /** @var array<string, array{label: string, configured: bool, issues: list<string>}> */
    public array $gatewayOptions = [];

    public function mount(
        ActivePaymentGatewayResolver $resolver,
        PaymentGatewayConfiguration $configuration,
    ): void {
        $this->gateways = collect(AccountTypes::cases())
            ->mapWithKeys(fn (AccountTypes $accountType): array => [
                $accountType->value => $resolver->resolve($accountType)->value,
            ])
            ->all();
        $this->accountTypeOptions = AccountTypes::options();
        $this->gatewayOptions = collect(PaymentGatewayName::cases())
            ->mapWithKeys(fn (PaymentGatewayName $gateway): array => [
                $gateway->value => [
                    'label' => $gateway->label(),
                    'configured' => $configuration->configured($gateway),
                    'issues' => $configuration->issues($gateway),
                ],
            ])
            ->all();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() === true;
    }

    public function save(UpdateActivePaymentGatewayAction $action): void
    {
        $validated = $this->validate([
            'gateways' => ['required', 'array'],
            'gateways.'.AccountTypes::INDIVIDUAL->value => ['required', Rule::enum(PaymentGatewayName::class)],
            'gateways.'.AccountTypes::BUSINESS->value => ['required', Rule::enum(PaymentGatewayName::class)],
            'gateways.'.AccountTypes::INSTITUTION->value => ['required', Rule::enum(PaymentGatewayName::class)],
        ]);
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $setting = $action->execute(
            $user,
            UpdatePaymentGatewaySettingsData::fromArray($validated['gateways']),
        );

        $this->gateways = collect(AccountTypes::cases())
            ->mapWithKeys(fn (AccountTypes $accountType): array => [
                $accountType->value => $setting->gatewayFor($accountType)->value,
            ])
            ->all();

        Notification::make()
            ->title('Payment gateways updated')
            ->body('New external payments will use the provider selected for their account type. Existing pending payments and mandates keep their recorded provider.')
            ->success()
            ->send();
    }
}
