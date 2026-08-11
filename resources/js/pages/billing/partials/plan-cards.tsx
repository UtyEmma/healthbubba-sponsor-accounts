import { CheckIcon, CircleHelpIcon, XIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { Plan } from '@/types';

function formatPrice(price: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(price));
}

export function PlanCard({
    plan,
    onSelect,
}: {
    plan: Plan;
    onSelect?: (plan: Plan) => void;
}) {
    const selectionDisabled = plan.is_current || !plan.checkout_available;
    const reasonId = `plan-${plan.id}-unavailable-reason`;

    return (
        <Card
            className={cn(
                'flex h-full flex-col',
                plan.is_current && 'border-secondary border-2',
            )}
        >
            <CardHeader className="gap-1 px-4 pt-6 pb-3">
                <div className="flex items-start justify-between gap-3">
                    <h3 className="text-base font-semibold">{plan.name}</h3>
                    {plan.is_current && (
                        <span className="rounded-full bg-success/10 px-2 py-1 text-xs font-medium text-success">
                            Current
                        </span>
                    )}
                </div>
                <p className="min-h-10 text-sm text-muted-foreground">
                    {plan.description}
                </p>
                <p className="pt-3 text-3xl leading-9 font-semibold tracking-tight">
                    {formatPrice(plan.price, plan.currency)}
                </p>
                <p className="text-xs text-muted-foreground">{plan.cadence}</p>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-4 px-4 pt-3">
                {plan.quotas.length > 0 && (
                    <dl className="grid gap-2 border-b pb-4 text-[13px]">
                        {plan.quotas.map((quota) => (
                            <div
                                key={quota.slug}
                                className="flex items-start justify-between gap-3"
                            >
                                <dt className="text-muted-foreground">
                                    {quota.name}
                                </dt>
                                <dd
                                    className={cn(
                                        'text-right font-medium',
                                        quota.quota === null &&
                                            'text-muted-foreground',
                                    )}
                                >
                                    {quota.description}
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}

                <ul className="grid gap-2 text-sm">
                    {plan.features.map((feature) => (
                        <li
                            key={feature.slug}
                            className={cn(
                                'flex items-center gap-2',
                                !feature.included && 'text-muted-foreground',
                            )}
                        >
                            {feature.included ? (
                                <CheckIcon className="size-4 shrink-0 text-success" />
                            ) : (
                                <XIcon className="size-4 shrink-0 text-muted-foreground/60" />
                            )}
                            <span>{feature.name}</span>
                            {feature.description && (
                                <CircleHelpIcon
                                    className="size-3.5 text-muted-foreground"
                                    aria-label={feature.description}
                                />
                            )}
                        </li>
                    ))}
                </ul>
            </CardContent>

            <CardFooter className="grid gap-2 px-4 pt-5 pb-6">
                <Button
                    type="button"
                    className="w-full"
                    disabled={selectionDisabled}
                    variant={plan.is_current ? 'muted' : 'primary'}
                    onClick={() => onSelect?.(plan)}
                    aria-describedby={
                        plan.unavailable_reason && !plan.is_current
                            ? reasonId
                            : undefined
                    }
                >
                    {plan.is_current
                        ? 'Current plan'
                        : plan.checkout_available
                          ? 'Choose plan'
                          : 'Checkout unavailable'}
                </Button>
                {plan.unavailable_reason && !plan.is_current && (
                    <p
                        id={reasonId}
                        className="text-center text-xs leading-5 text-muted-foreground"
                    >
                        {plan.unavailable_reason}
                    </p>
                )}
            </CardFooter>
        </Card>
    );
}
