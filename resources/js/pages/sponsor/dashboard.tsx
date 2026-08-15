import { Head, Link, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import {
    ConsultationPoolCard,
    MetricCard,
    RecentActivityCard,
    SubscriptionCard,
} from '@/pages/sponsor/partials/dashboard-cards';
import type { DashboardStatistic } from '@/pages/sponsor/partials/dashboard-cards';
import beneficiaries from '@/routes/beneficiaries';
import type { DashboardPageProps } from '@/types';

export default function IndividualDashboard({ dashboard }: DashboardPageProps) {
    const {
        auth: { user },
    } = usePage().props;
    const gpAllocation = dashboard.coverage.allocations.find(
        (allocation) => allocation.type === 'gp',
    );
    const statistics: DashboardStatistic[] = [
        {
            icon: 'dashboard-beneficiaries.svg',
            label: 'Active beneficiaries',
            value: String(dashboard.beneficiaries.active),
            detail: dashboard.subscription
                ? `of ${dashboard.beneficiaries.capacity} max on ${dashboard.subscription.planName}`
                : 'No active plan capacity',
        },
        {
            icon: 'dashboard-wallet.svg',
            label: 'Wallet balance',
            value: formatMoney(
                dashboard.wallet.balance,
                dashboard.wallet.currency,
            ),
            detail: 'Available to spend',
        },
        {
            icon: 'dashboard-consults.svg',
            label: 'GP consults left',
            value:
                gpAllocation?.remaining === null
                    ? '∞'
                    : String(gpAllocation?.remaining ?? 0),
            detail:
                gpAllocation?.limit === null
                    ? 'Unlimited shared pool'
                    : `Shared pool of ${gpAllocation?.limit ?? 0}`,
        },
        {
            icon: 'dashboard-renewal.svg',
            label: 'Renews in',
            value:
                dashboard.subscription?.renewalDays === null ||
                dashboard.subscription?.renewalDays === undefined
                    ? '--'
                    : `${dashboard.subscription.renewalDays}d`,
            detail: formatDate(dashboard.subscription?.renewsAt ?? null),
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title={`Welcome back, ${user.name}`}
                        description="Here's an overview of your sponsorship and family coverage."
                        action={
                            <Link
                                href={beneficiaries.index()}
                                className={cn(
                                    buttonVariants({ size: 'compact' }),
                                    'self-start sm:self-auto',
                                )}
                            >
                                Manage beneficiaries
                            </Link>
                        }
                    />

                    <section
                        className="grid gap-4 pt-6 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Sponsorship overview"
                    >
                        {statistics.map((statistic) => (
                            <MetricCard
                                key={statistic.label}
                                statistic={statistic}
                            />
                        ))}
                    </section>

                    <section
                        className="grid gap-4 pt-8 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]"
                        aria-label="Plan details"
                    >
                        <ConsultationPoolCard
                            allocations={dashboard.coverage.allocations}
                        />
                        <SubscriptionCard
                            subscription={dashboard.subscription}
                            currency={dashboard.wallet.currency}
                        />
                    </section>

                    <section className="pt-4" aria-label="Recent activity">
                        <RecentActivityCard
                            activities={dashboard.recentActivities}
                        />
                    </section>
                </div>
            </DashboardLayout>
        </>
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

function formatDate(value: string | null): string {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
