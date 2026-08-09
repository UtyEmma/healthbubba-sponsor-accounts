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

const nairaFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

export function PlanCard({
    plan,
    onSelect,
}: {
    plan: Plan;
    onSelect?: (plan: Plan) => void;
}) {
    return (
        <Card
            className={cn(
                'flex h-full flex-col',
                plan.is_current && 'border-success ring-1 ring-success/20',
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
                    {nairaFormatter.format(Number(plan.price))}
                </p>
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

            <CardFooter className="px-4 pt-5 pb-6">
                <Button
                    type="button"
                    className="w-full"
                    disabled={plan.is_current}
                    variant={plan.is_current ? 'muted' : 'primary'}
                    onClick={() => onSelect?.(plan)}
                >
                    {plan.is_current ? 'Current plan' : 'Select plan'}
                </Button>
            </CardFooter>
        </Card>
    );
}
