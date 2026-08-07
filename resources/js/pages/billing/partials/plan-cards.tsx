import { CheckIcon, CircleHelpIcon, XIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { BillingFeature, Plan } from '@/types';

const nairaFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

const actionLabels: Record<Plan['action'], string> = {
    current: 'Current plan',
    downgrade: 'Downgrade',
    upgrade: 'Upgrade',
    select: 'Select plan',
};

function featureValue(feature: BillingFeature): string {
    const parts = [feature.value];

    if (feature.resetLabel) {
        parts.push(feature.resetLabel);
    }

    if (feature.unitPrice) {
        parts.push(
            `${nairaFormatter.format(Number(feature.unitPrice))} overage`,
        );
    }

    return parts.filter(Boolean).join(' · ');
}

export function PlanCard({
    plan,
}: {
    plan: Plan;
}) {
    console.log(plan.features)
    const allowanceFeatures = plan.features.filter(
        (feature) => feature.included && feature.value !== null,
    );

    const entitlementFeatures = plan.features.filter(
        (feature) => feature.value === null,
    );

    return (
        <Card
            className={cn(
                'flex h-full flex-col',
                plan.isCurrent && 'border-success ring-1 ring-success/20',
            )}
        >
            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                <div className="flex items-start justify-between gap-3">
                    <h3 className="text-base font-semibold">{plan.name}</h3>
                    {plan.isCurrent && (
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
                    {/* <span className="pl-1 text-sm font-normal text-muted-foreground">
                        {plan.description}
                    </span> */}
                </p>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col gap-4 px-6 pt-3">
                {allowanceFeatures.length > 0 && (
                    <dl className="grid gap-2 border-b pb-4 text-sm">
                        {allowanceFeatures.map((feature) => (
                            <div
                                key={feature.slug}
                                className="flex justify-between gap-3"
                            >
                                <dt className="text-muted-foreground">
                                    {feature.label}
                                </dt>
                                <dd className="text-right font-medium">
                                    {featureValue(feature)}
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
            <CardFooter className="px-6 pt-5 pb-6">
                <Button
                    className="w-full"
                    disabled={plan.is_current}
                    variant={
                        plan.is_current
                            ? 'muted'
                            : plan.action === 'downgrade'
                              ? 'outline'
                              : 'primary'
                    }
                    >
                    {plan.is_current ? 'Is Current' : 'Select Plan'}
                </Button>
            </CardFooter>
        </Card>
    );
}
