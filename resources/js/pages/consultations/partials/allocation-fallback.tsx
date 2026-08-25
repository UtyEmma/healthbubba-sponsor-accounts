import { Form, usePage } from '@inertiajs/react';
import { CreditCardIcon, WalletIcon } from 'lucide-react';
import type { ComponentProps } from 'react';

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

export function AllocationFallbackCard({
    className,
}: Pick<ComponentProps<typeof Card>, 'className'>) {
    const { workspace } = usePage().props;
    const isBusiness = workspace.type === 'business';
    const subject = isBusiness ? 'Employee' : 'Beneficiary';
    const subjects = isBusiness ? 'employees' : 'beneficiaries';
    const options: AllocationFallbackOption[] = [
        {
            value: 'beneficiary_wallet',
            icon: WalletIcon,
            title: `${subject} wallet`,
            description: 'Paid from their own balance',
        },
        {
            value: 'card_payment',
            icon: CreditCardIcon,
            title: 'Card payment',
            description: 'Direct external checkout',
        },
    ];

    return (
        <Form
            {...allocationFallback.update.form()}
            options={{ preserveScroll: true }}
        >
            {({ errors, processing }) => (
                <Card className={cn('mt-5', className)}>
                    <CardHeader className="px-6 pt-7">
                        <CardTitle className="text-base leading-5">
                            When allocations run out
                        </CardTitle>
                        <p className="text-sm leading-5 text-muted-foreground">
                            Care isn&apos;t blocked; {subjects} unlock direct
                            checkout via:
                        </p>
                    </CardHeader>

                    <CardContent className="grid gap-3 px-6 pb-6">
                        {options.map(
                            ({ value, icon: Icon, title, description }) => (
                                <label
                                    key={value}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl border border-border p-2',
                                        workspace.fallbackChannel === value &&
                                            'border-secondary bg-success-muted',
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
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-muted text-secondary">
                                        <Icon className="size-5" />
                                    </span>
                                    <div>
                                        <h3 className="leading-5 text-sm font-medium">
                                            {title}
                                        </h3>
                                        <p className="text-xs leading-4 text-muted-foreground">
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
