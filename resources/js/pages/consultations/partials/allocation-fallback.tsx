import { Form, usePage } from '@inertiajs/react';
import { CreditCardIcon, WalletIcon } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import allocationFallback from '@/routes/consultations/allocation_fallback';
import type { AllocationFallback } from '@/types';

interface AllocationFallbackOption {
    value: AllocationFallback;
    icon: typeof WalletIcon;
    title: string;
    description: string;
}

const options: AllocationFallbackOption[] = [
    {
        value: 'beneficiary_wallet',
        icon: WalletIcon,
        title: 'Beneficiary wallet',
        description: 'Paid from their own balance',
    },
    {
        value: 'card_payment',
        icon: CreditCardIcon,
        title: 'Card payment',
        description: 'Direct external checkout',
    },
];

export function AllocationFallbackCard() {
    const { workspace } = usePage().props;

    return (
        <Form
            {...allocationFallback.update.form()}
            options={{ preserveScroll: true }}
        >
            {({ errors, processing }) => (
                <Card className="mt-5">
                    <CardHeader className="px-6 pt-7 pb-5">
                        <CardTitle className="text-base leading-5">
                            When allocations run out
                        </CardTitle>
                        <p className="text-sm leading-5 text-muted-foreground">
                            Care isn’t blocked; beneficiaries unlock direct
                            checkout via:
                        </p>
                    </CardHeader>

                    <CardContent className="grid gap-3 px-6 pb-6">
                        {options.map(
                            ({ value, icon: Icon, title, description }) => (
                                <label
                                    key={value}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl border border-border p-4',
                                        workspace.fallbackChannel === value &&
                                            'border-primary bg-success-muted',
                                        processing
                                            ? 'cursor-wait'
                                            : 'cursor-pointer',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="fallback_channel"
                                        value={value}
                                        checked={
                                            workspace.fallbackChannel === value
                                        }
                                        disabled={processing}
                                        onChange={(event) => {
                                            event.currentTarget.form?.requestSubmit();
                                        }}
                                        className="sr-only"
                                    />
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-muted text-success">
                                        <Icon className="size-5" />
                                    </span>
                                    <div>
                                        <h3 className="leading-5 font-medium">
                                            {title}
                                        </h3>
                                        <p className="text-sm leading-4 text-muted-foreground">
                                            {description}
                                        </p>
                                    </div>
                                </label>
                            ),
                        )}

                        {errors.fallback_channel && (
                            <p className="text-sm text-destructive">
                                {errors.fallback_channel}
                            </p>
                        )}
                    </CardContent>
                </Card>
            )}
        </Form>
    );
}
