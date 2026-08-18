import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRightIcon,
    CalendarClockIcon,
    StethoscopeIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { DashboardLayout } from '@/layouts/dashboard';
import { BusinessMetricCard } from '@/pages/business-sponsor/partials/business-metric-card';
import institutional from '@/routes/institutional';
import type {
    ConsultationAllocationSummary,
    DashboardPageProps,
} from '@/types';

import { ConsultationTrendsChart } from './partials/consultation-trends-chart';

export default function InstitutionalDashboard({
    dashboard,
}: DashboardPageProps) {
    const [announcement, setAnnouncement] = useState('');
    const { workspace } = usePage().props;
    const gpAllocation = dashboard.coverage.allocations.find(
        (allocation) => allocation.type === 'gp',
    );
    const specialistAllocation = dashboard.coverage.allocations.find(
        (allocation) => allocation.type === 'specialist',
    );
    const fundedBudget = Math.max(
        Number(dashboard.wallet.totalFunded),
        Number(dashboard.wallet.balance),
    );

    return (
        <>
            <Head title={workspace.name} />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl pb-4">
                    <PageHeader
                        title={workspace.name}
                        description="Program reach and coverage utilization at a glance."
                        action={
                            <Link
                                href={institutional.campaigns.index()}
                                className={buttonVariants({
                                    size: 'compact',
                                })}
                            >
                                Manage Campaigns
                            </Link>
                        }
                    />

                    <section
                        className="grid gap-4 pt-6 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Institutional coverage overview"
                    >
                        <BusinessMetricCard
                            label="Total beneficiaries"
                            value={String(dashboard.beneficiaries.total)}
                            icon={UsersRoundIcon}
                            tone="green"
                        />
                        <BusinessMetricCard
                            label="Active beneficiaries"
                            value={String(dashboard.beneficiaries.active)}
                            icon={UserRoundCheckIcon}
                            tone="blue"
                        />
                        <BusinessMetricCard
                            label="Coverage balance"
                            value={formatMoney(
                                dashboard.wallet.balance,
                                dashboard.wallet.currency,
                            )}
                            icon={WalletCardsIcon}
                        />
                        <BusinessMetricCard
                            label="Coverage expires in"
                            value={
                                dashboard.subscription?.renewalDays === null ||
                                dashboard.subscription?.renewalDays ===
                                    undefined
                                    ? '--'
                                    : `${dashboard.subscription.renewalDays}d`
                            }
                            icon={CalendarClockIcon}
                            tone="amber"
                        />
                    </section>

                    <section className="grid gap-4 pt-4 md:grid-cols-3">
                        <BusinessMetricCard
                            label="GP remaining"
                            value={allocationRemaining(gpAllocation)}
                            icon={StethoscopeIcon}
                        />
                        <BusinessMetricCard
                            label="Specialist remaining"
                            value={allocationRemaining(specialistAllocation)}
                            icon={StethoscopeIcon}
                        />
                        <BusinessMetricCard
                            label="Consultations completed"
                            value={String(dashboard.completedConsultations)}
                            icon={StethoscopeIcon}
                            tone="blue"
                        />
                    </section>

                    <section className="grid gap-4 pt-5 lg:grid-cols-[2fr_1fr]">
                        <Card>
                            <CardHeader className="gap-1 px-6 pt-6 pb-1">
                                <CardTitle className="text-base">
                                    Consultation trends
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Sponsored consultations completed per month.
                                </p>
                            </CardHeader>
                            <CardContent className="px-5 pt-1 pb-5">
                                <ConsultationTrendsChart
                                    data={dashboard.consultationTrends}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="gap-2 px-6 pt-6 pb-3">
                                <div className="flex items-center justify-between gap-4">
                                    <CardTitle className="text-base">
                                        Coverage utilization
                                    </CardTitle>
                                    <span className="text-xs font-medium text-success">
                                        {dashboard.subscription?.statusLabel ??
                                            'Inactive'}
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {dashboard.subscription?.planName ??
                                        'No active coverage plan'}
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-5 px-6 pt-3">
                                <CoverageProgress
                                    label="GP coverage"
                                    value={allocationProgressLabel(
                                        gpAllocation,
                                    )}
                                    progress={allocationProgress(gpAllocation)}
                                />
                                <CoverageProgress
                                    label="Specialist coverage"
                                    value={allocationProgressLabel(
                                        specialistAllocation,
                                    )}
                                    progress={allocationProgress(
                                        specialistAllocation,
                                    )}
                                />
                                <CoverageProgress
                                    label="Budget"
                                    value={`${formatNumber(
                                        dashboard.wallet.balance,
                                    )} / ${formatNumber(
                                        String(fundedBudget),
                                    )} left`}
                                    progress={consumptionProgress(
                                        Number(dashboard.wallet.balance),
                                        fundedBudget,
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </section>

                    <Card className="mt-4">
                        <CardHeader className="flex-row items-center justify-between px-6 pt-6 pb-3">
                            <CardTitle className="text-base">
                                Recent activity
                            </CardTitle>
                            <Button
                                variant="ghost"
                                size="compact"
                                onClick={() =>
                                    setAnnouncement('Notifications selected.')
                                }
                            >
                                Notifications
                                <ArrowRightIcon className="size-4" />
                            </Button>
                        </CardHeader>
                        <CardContent className="px-6 pt-3 pb-6">
                            <ul className="grid gap-3">
                                {dashboard.recentActivities.length > 0 ? (
                                    dashboard.recentActivities.map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex items-start gap-3 text-sm"
                                        >
                                            <span className="mt-2 size-1.5 shrink-0 rounded-full bg-success" />
                                            <span>
                                                <span className="block">
                                                    {item.title}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    {item.actor.name} ·{' '}
                                                    {formatDate(
                                                        item.occurredAt,
                                                    )}
                                                </span>
                                            </span>
                                        </li>
                                    ))
                                ) : (
                                    <li className="text-sm text-muted-foreground">
                                        No recent activity
                                    </li>
                                )}
                            </ul>
                        </CardContent>
                    </Card>

                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </DashboardLayout>
        </>
    );
}

function CoverageProgress({
    label,
    value,
    progress,
}: {
    label: string;
    value: string;
    progress: number;
}) {
    return (
        <div className="grid gap-2">
            <div className="flex justify-between gap-4 text-sm">
                <span>{label}</span>
                <span className="font-semibold text-muted-foreground">
                    {value}
                </span>
            </div>
            <Progress value={progress} aria-label={`${label}: ${value}`} />
        </div>
    );
}

function allocationRemaining(
    allocation: ConsultationAllocationSummary | undefined,
): string {
    if (!allocation || allocation.unavailableReason) {
        return '0';
    }

    return allocation.remaining === null ? '∞' : String(allocation.remaining);
}

function allocationProgressLabel(
    allocation: ConsultationAllocationSummary | undefined,
): string {
    if (!allocation || allocation.unavailableReason) {
        return 'Unavailable';
    }

    if (allocation.limit === null) {
        return 'Unlimited';
    }

    return `${allocation.remaining ?? 0} / ${allocation.limit} left`;
}

function allocationProgress(
    allocation: ConsultationAllocationSummary | undefined,
): number {
    if (!allocation || allocation.limit === null || allocation.limit === 0) {
        return 0;
    }

    return consumptionProgress(allocation.remaining ?? 0, allocation.limit);
}

function consumptionProgress(remaining: number, total: number): number {
    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, ((total - remaining) / total) * 100));
}

function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatNumber(amount: string): string {
    return new Intl.NumberFormat('en-NG', {
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
