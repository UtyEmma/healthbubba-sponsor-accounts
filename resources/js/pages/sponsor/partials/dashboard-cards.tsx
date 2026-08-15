import { Link } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import plans from '@/routes/plans';
import type {
    ConsultationAllocationSummary,
    DashboardSubscription,
    WorkspaceActivity,
} from '@/types';

export type DashboardStatistic = {
    icon: string;
    label: string;
    value: string;
    detail: string;
};

export function MetricCard({ statistic }: { statistic: DashboardStatistic }) {
    return (
        <Card className="flex min-h-[108px] items-center gap-4 p-5">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-card shadow-card">
                <img
                    src={`/images/sponsor/${statistic.icon}`}
                    alt=""
                    className="size-5"
                />
            </span>
            <div className="min-w-0">
                <p className="text-sm leading-5 text-muted-foreground">
                    {statistic.label}
                </p>
                <p className="text-2xl leading-8 font-semibold text-foreground">
                    {statistic.value}
                </p>
                <p className="truncate text-xs leading-4 text-muted-foreground">
                    {statistic.detail}
                </p>
            </div>
        </Card>
    );
}

export function ConsultationPoolCard({
    allocations,
}: {
    allocations: ConsultationAllocationSummary[];
}) {
    const rows = [
        allocationRow(allocations, 'gp', 'GP Consultations'),
        allocationRow(allocations, 'specialist', 'Specialist Consultations'),
    ];

    return (
        <Card className="h-full overflow-hidden">
            <CardHeader className="flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle className="text-sm leading-4">
                        Shared consultation pool
                    </CardTitle>
                    <p className="pt-2 text-sm leading-5 text-muted-foreground">
                        Allocations are shared across all active beneficiaries,
                        first come first served.
                    </p>
                </div>
                <button
                    type="button"
                    className={buttonVariants({
                        variant: 'outline',
                        size: 'sm',
                    })}
                >
                    Details
                </button>
            </CardHeader>
            <CardContent className="px-6 pt-2.5 pb-5">
                {rows.map((row, index) => (
                    <div key={row.label} className={index === 1 ? 'pt-5' : ''}>
                        <div className="flex items-center justify-between gap-4 text-sm leading-5">
                            <span>{row.label}</span>
                            <span>{row.value}</span>
                        </div>
                        <Progress
                            value={row.progress}
                            aria-label={`${row.label} remaining`}
                            className="mt-1.5"
                        />
                    </div>
                ))}
                <p className="pt-5 text-xs leading-4 text-subtle">
                    Unused allocations reset to zero on your renewal date — no
                    rollover.
                </p>
            </CardContent>
        </Card>
    );
}

export function SubscriptionCard({
    subscription,
    currency,
}: {
    subscription: DashboardSubscription | null;
    currency: string;
}) {
    return (
        <Card className="h-full overflow-hidden">
            <CardHeader className="flex-row items-center justify-between border-b border-border">
                <CardTitle className="text-sm leading-4">
                    Subscription
                </CardTitle>
                <Badge
                    variant={subscription?.active ? 'success' : 'destructive'}
                >
                    {subscription?.statusLabel ?? 'Inactive'}
                </Badge>
            </CardHeader>
            <CardContent className="px-6 pt-2.5 pb-5">
                <dl className="grid text-sm leading-5">
                    <div className="flex h-5 justify-between">
                        <dt className="text-muted-foreground">Plan</dt>
                        <dd className="font-medium">
                            {subscription?.planName ?? 'No active plan'}
                        </dd>
                    </div>
                    <div className="flex h-8 items-end justify-between">
                        <dt className="text-muted-foreground">
                            {subscription?.billingCycleLabel ?? 'Billing'}
                        </dt>
                        <dd className="font-medium">
                            {formatMoney(
                                subscription?.renewalAmount ?? '0',
                                currency,
                            )}
                        </dd>
                    </div>
                    <div className="flex h-8 items-end justify-between">
                        <dt className="text-muted-foreground">
                            Extra beneficiaries
                        </dt>
                        <dd className="font-medium">
                            {subscription?.additionalCapacity ?? 0}
                        </dd>
                    </div>
                </dl>
                <Separator className="mt-3" />
                <Link
                    href={plans.index()}
                    className={cn(
                        buttonVariants({ variant: 'muted' }),
                        'mt-3 w-full',
                    )}
                >
                    Manage plan
                    <img
                        src="/images/sponsor/dashboard-arrow.svg"
                        alt=""
                        className="size-4"
                    />
                </Link>
            </CardContent>
        </Card>
    );
}

export function RecentActivityCard({
    activities,
}: {
    activities: WorkspaceActivity[];
}) {
    const activity = activities.at(0);

    return (
        <Card className="min-h-[166px] max-w-[418px] overflow-hidden">
            <CardHeader className="h-[72px] justify-center border-b border-border px-6 py-0">
                <CardTitle className="text-lg leading-6">
                    Recent activity
                </CardTitle>
            </CardHeader>
            <CardContent className="flex min-h-[94px] items-start justify-center px-6 pt-3 text-center">
                {activity ? (
                    <div>
                        <p className="text-[13px] leading-[18px] font-medium">
                            {activity.title}
                        </p>
                        <p className="pt-2 text-[13px] leading-[18px] text-muted-foreground">
                            {activity.actor.name} ·{' '}
                            {formatActivityDate(activity.occurredAt)}
                        </p>
                    </div>
                ) : (
                    <div>
                        <p className="text-[13px] leading-[18px] font-medium">
                            No recent activity
                        </p>
                        <p className="pt-2 text-[13px] leading-[18px] text-muted-foreground">
                            Your activity updates will appear here in real time.
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function allocationRow(
    allocations: ConsultationAllocationSummary[],
    type: ConsultationAllocationSummary['type'],
    label: string,
): { label: string; value: string; progress: number } {
    const allocation = allocations.find((item) => item.type === type);

    if (!allocation || allocation.unavailableReason) {
        return { label, value: 'Unavailable', progress: 0 };
    }

    if (allocation.limit === null) {
        return { label, value: 'Unlimited', progress: 0 };
    }

    const used = allocation.completed + allocation.reserved;

    return {
        label,
        value: `${allocation.remaining ?? 0} / ${allocation.limit} left`,
        progress:
            allocation.limit > 0
                ? Math.min(100, (used / allocation.limit) * 100)
                : 0,
    };
}

function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatActivityDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
