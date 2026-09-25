<?php

namespace App\Filament\Pages;

use App\Actions\Payments\UpdateActivePaymentGatewayAction;
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

    protected static ?string $title = 'Payment Gateway';

    public string $gateway = PaymentGatewayName::PAYSTACK->value;

    /** @var array<string, array{label: string, configured: bool, issues: list<string>}> */
    public array $gatewayOptions = [];

    public function mount(
        ActivePaymentGatewayResolver $resolver,
        PaymentGatewayConfiguration $configuration,
    ): void {
        $this->gateway = $resolver->resolve()->value;
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
            'gateway' => ['required', Rule::enum(PaymentGatewayName::class)],
        ]);
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $setting = $action->execute($user, PaymentGatewayName::from($validated['gateway']));

        $this->gateway = $setting->active_gateway->value;

        Notification::make()
            ->title("{$setting->active_gateway->label()} is now active")
            ->body('New external payments will use this provider. Existing pending payments and mandates keep their recorded provider.')
            ->success()
            ->send();
    }
}
