import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRightIcon,
    CalendarClockIcon,
    StethoscopeIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import business from '@/routes/business';
import type { DashboardPageProps } from '@/types';

import { BusinessMetricCard } from './partials/business-metric-card';
import { DepartmentUtilizationChart } from './partials/department-utilization-chart';

export default function BusinessDashboard({ dashboard }: DashboardPageProps) {
    const [announcement, setAnnouncement] = useState('');
    const { workspace } = usePage().props;
    const gpAllocation = dashboard.coverage.allocations.find(
        (allocation) => allocation.type === 'gp',
    );

    return (
        <>
            <Head title={workspace.name} />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title={workspace.name}
                        description="Workforce healthcare coverage at a glance."
                        action={
                            <Link
                                href={business.employees()}
                                className={`${buttonVariants({ size: 'compact' })} self-start sm:self-auto`}
                            >
                                Manage Employees
                            </Link>
                        }
                    />

                    <section
                        className="grid gap-4 pt-6 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Business overview"
                    >
                        <BusinessMetricCard
                            label="Active Employees"
                            value={String(dashboard.beneficiaries.active)}
                            icon={UsersRoundIcon}
                            tone="green"
                        />
                        <BusinessMetricCard
                            label="Wallet balance"
                            value={formatMoney(
                                dashboard.wallet.balance,
                                dashboard.wallet.currency,
                            )}
                            icon={WalletCardsIcon}
                        />
                        <BusinessMetricCard
                            label="Scheduled consultations left"
                            value={
                                gpAllocation?.remaining === null
                                    ? '∞'
                                    : String(gpAllocation?.remaining ?? 0)
                            }
                            icon={StethoscopeIcon}
                            tone="blue"
                        />
                        <BusinessMetricCard
                            label="Renews in"
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

                    <section className="grid gap-5 pt-8 lg:grid-cols-[2fr_1fr]">
                        <Card>
                            <CardHeader className="gap-1 border-b px-6 py-4">
                                <CardTitle className="text-base font-semibold">
                                    Consultation utilization by department
                                </CardTitle>
                                <p className="text-xs leading-4 text-muted-foreground">
                                    Each employee holds an isolated per-seat
                                    allocation — unused consults never transfer
                                    between staff.
                                </p>
                            </CardHeader>
                            <CardContent className="px-4 pt-3 pb-4">
                                <DepartmentUtilizationChart
                                    data={dashboard.departmentUtilization}
                                />
                            </CardContent>
                        </Card>

                        <Card className="self-start">
                            <CardHeader className="flex-row items-center justify-between border-b px-6 py-5">
                                <CardTitle className="text-base font-semibold">
                                    Subscription
                                </CardTitle>
                                <Badge
                                    variant={
                                        dashboard.subscription?.active
                                            ? 'success'
                                            : 'destructive'
                                    }
                                >
                                    {dashboard.subscription?.statusLabel ??
                                        'Inactive'}
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-6 pt-3">
                                <dl className="grid gap-3 border-b pb-4 text-sm">
                                    <SubscriptionRow
                                        label="Plan"
                                        value={
                                            dashboard.subscription?.planName ??
                                            'No active plan'
                                        }
                                    />
                                    <SubscriptionRow
                                        label={
                                            dashboard.subscription
                                                ?.billingCycleLabel ?? 'Billing'
                                        }
                                        value={formatMoney(
                                            dashboard.subscription
                                                ?.renewalAmount ?? '0',
                                            dashboard.wallet.currency,
                                        )}
                                    />
                                    <SubscriptionRow
                                        label="Extra seats"
                                        value={String(
                                            dashboard.subscription
                                                ?.additionalCapacity ?? 0,
                                        )}
                                    />
                                </dl>
                                <Button
                                    variant="muted"
                                    className="mt-3 w-full"
                                    onClick={() =>
                                        setAnnouncement(
                                            'Plan management selected.',
                                        )
                                    }
                                >
                                    Manage plan
                                    <ArrowRightIcon className="size-4" />
                                </Button>
                            </CardContent>
                        </Card>
                    </section>

                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </DashboardLayout>
        </>
    );
}

function SubscriptionRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}
