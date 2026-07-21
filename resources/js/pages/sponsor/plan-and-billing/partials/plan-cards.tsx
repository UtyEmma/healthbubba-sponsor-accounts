import { CheckIcon, CircleHelpIcon, XIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type Plan = {
    name: string;
    audience: string;
    price: string;
    details: Array<[string, string]>;
    features: Array<{ label: string; included: boolean; help?: boolean }>;
    action: 'Downgrade' | 'Current plan' | 'Upgrade';
};

export function PlanCard({
    plan,
    onUpgrade,
}: {
    plan: Plan;
    onUpgrade: () => void;
}) {
    const isCurrent = plan.action === 'Current plan';

    return (
        <Card
            className={cn(
                'flex min-h-[563px] flex-col',
                isCurrent && 'border-success',
            )}
        >
            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                <h3 className="text-base font-semibold">{plan.name}</h3>
                <p className="text-sm text-muted-foreground">{plan.audience}</p>
                <p className="pt-3 text-3xl leading-9 font-semibold tracking-[-0.7px]">
                    {plan.price}
                    <span className="text-sm font-normal text-muted-foreground">
                        /mo
                    </span>
                </p>
            </CardHeader>
            <CardContent className="flex-1 px-6 pt-3">
                <dl className="grid gap-2 border-b pb-4 text-sm">
                    {plan.details.map(([label, value]) => (
                        <div key={label} className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">{label}</dt>
                            <dd className="text-right font-medium">{value}</dd>
                        </div>
                    ))}
                </dl>
                <ul className="grid gap-2 pt-4 text-sm">
                    {plan.features.map((feature) => (
                        <li
                            key={feature.label}
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
                            <span>{feature.label}</span>
                            {feature.help && (
                                <CircleHelpIcon className="size-3.5 text-muted-foreground" />
                            )}
                        </li>
                    ))}
                </ul>
            </CardContent>
            <CardFooter className="px-6 pb-6">
                {plan.action === 'Downgrade' ? (
                    <Button
                        variant="ghost"
                        className="w-full text-destructive hover:text-destructive"
                    >
                        Downgrade
                    </Button>
                ) : (
                    <Button
                        className="w-full"
                        disabled={isCurrent}
                        variant={isCurrent ? 'muted' : 'primary'}
                        onClick={
                            plan.action === 'Upgrade' ? onUpgrade : undefined
                        }
                    >
                        {plan.action}
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}
