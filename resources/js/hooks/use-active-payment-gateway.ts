import { usePage } from '@inertiajs/react';

import type { ActivePaymentGateway } from '@/types';

const paystackFallback: ActivePaymentGateway = {
    name: 'paystack',
    label: 'Paystack',
};

export function useActivePaymentGateway(): ActivePaymentGateway {
    return usePage().props.activePaymentGateway ?? paystackFallback;
}
