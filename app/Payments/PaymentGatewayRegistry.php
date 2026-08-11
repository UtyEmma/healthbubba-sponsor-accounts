<?php

namespace App\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\Payments\GatewayConfigurationException;
use App\Exceptions\Payments\PaymentGatewayNotConfiguredException;

final readonly class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways;

    /** @param iterable<PaymentGateway> $gateways */
    public function __construct(
        iterable $gateways,
        private PaymentGatewayName $defaultGateway,
    ) {
        $registeredGateways = [];

        foreach ($gateways as $gateway) {
            $name = $gateway->name()->value;

            if (isset($registeredGateways[$name])) {
                throw new GatewayConfigurationException("The [{$name}] payment gateway is registered more than once.");
            }

            $registeredGateways[$name] = $gateway;
        }

        if (! isset($registeredGateways[$this->defaultGateway->value])) {
            throw new GatewayConfigurationException("The default [{$this->defaultGateway->value}] payment gateway is not registered.");
        }

        $this->gateways = $registeredGateways;
    }

    public function resolve(?PaymentGatewayName $gateway = null): PaymentGateway
    {
        $gateway ??= $this->defaultGateway;

        if (! array_key_exists($gateway->value, $this->gateways)) {
            throw new PaymentGatewayNotConfiguredException($gateway);
        }

        return $this->gateways[$gateway->value];
    }

    public function gatewayName(?PaymentGatewayName $gateway = null): PaymentGatewayName
    {
        return $this->resolve($gateway)->name();
    }
}
