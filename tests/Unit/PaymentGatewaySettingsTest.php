<?php

use App\Actions\Payments\UpdateActivePaymentGatewayAction;
use App\Enums\Account\Roles;
use App\Enums\Payments\PaymentGatewayName;
use App\Filament\Pages\PaymentGatewaySettings;
use App\Http\Resources\ActivePaymentGatewayResource;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use App\Payments\ActivePaymentGatewayResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('role');
        $table->string('status');
        $table->string('password');
        $table->timestamps();
    });
    Schema::create('payment_gateway_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('active_gateway');
        $table->foreignId('updated_by_user_id')->nullable();
        $table->timestamps();
    });

    config()->set('payments.gateways.paystack.secret_key', 'paystack-test-secret');
    config()->set('payments.gateways.stripe.secret_key', 'stripe-test-secret');
    config()->set('payments.gateways.stripe.webhook_secret', 'whsec_test');
});

afterEach(function (): void {
    Schema::dropIfExists('payment_gateway_settings');
    Schema::dropIfExists('users');
});

it('allows an administrator to select the gateway used for new external payments', function (): void {
    $admin = createGatewayTestUser(Roles::ADMIN, 'admin@example.com');
    $action = app(UpdateActivePaymentGatewayAction::class);

    $setting = $action->execute($admin, PaymentGatewayName::STRIPE);

    expect($setting->active_gateway)->toBe(PaymentGatewayName::STRIPE)
        ->and($setting->updated_by_user_id)->toBe($admin->getKey())
        ->and(app(ActivePaymentGatewayResolver::class)->resolve())->toBe(PaymentGatewayName::STRIPE);

    $this->assertDatabaseHas('payment_gateway_settings', [
        'id' => PaymentGatewaySetting::SINGLETON_ID,
        'active_gateway' => PaymentGatewayName::STRIPE->value,
        'updated_by_user_id' => $admin->getKey(),
    ]);
});

it('prevents non administrators and unconfigured gateways from being activated', function (): void {
    $user = createGatewayTestUser(Roles::USER, 'user@example.com');
    $action = app(UpdateActivePaymentGatewayAction::class);

    expect(fn () => $action->execute($user, PaymentGatewayName::STRIPE))
        ->toThrow(AuthorizationException::class);

    config()->set('payments.gateways.stripe.webhook_secret');
    $admin = createGatewayTestUser(Roles::SUPER_ADMIN, 'super@example.com');

    expect(fn () => $action->execute($admin, PaymentGatewayName::STRIPE))
        ->toThrow(ValidationException::class);
});

it('restricts the Filament payment gateway page to administrators', function (): void {
    $admin = createGatewayTestUser(Roles::ADMIN, 'admin@example.com');
    $user = createGatewayTestUser(Roles::USER, 'user@example.com');

    actingAs($admin);
    get(PaymentGatewaySettings::getUrl())->assertSuccessful();
    Livewire::test(PaymentGatewaySettings::class)
        ->set('gateway', PaymentGatewayName::STRIPE->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(ActivePaymentGatewayResolver::class)->resolve())->toBe(PaymentGatewayName::STRIPE);

    actingAs($user);
    get(PaymentGatewaySettings::getUrl())->assertForbidden();
});

it('serializes the active payment gateway contract', function (): void {
    $resource = new ActivePaymentGatewayResource(PaymentGatewayName::STRIPE);

    expect($resource->toArray(Request::create('/')))->toBe([
        'name' => 'stripe',
        'label' => 'Stripe',
    ]);
});

function createGatewayTestUser(Roles $role, string $email): User
{
    return User::query()->create([
        'name' => 'Gateway Administrator',
        'email' => $email,
        'role' => $role,
        'password' => 'password',
    ]);
}
