export type PaymentGatewayName = 'paystack' | 'stripe';

export type ActivePaymentGateway = {
    name: PaymentGatewayName;
    label: string;
};
