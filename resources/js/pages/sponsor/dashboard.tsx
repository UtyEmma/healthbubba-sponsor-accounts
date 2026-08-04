import { Head, Link, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    ConsultationPoolCard,
    MetricCard,
    RecentActivityCard,
    SubscriptionCard,
} from '@/pages/sponsor/partials/dashboard-cards';
import type { DashboardStatistic } from '@/pages/sponsor/partials/dashboard-cards';
import { home } from '@/routes';

const statistics: DashboardStatistic[] = [
    {
        icon: 'dashboard-beneficiaries.svg',
        label: 'Active beneficiaries',
        value: '0',
        detail: 'of 12 max on Premium Plan',
    },
    {
        icon: 'dashboard-wallet.svg',
        label: 'Wallet balance',
        value: '₦0.00',
        detail: 'Available to transfer',
    },
    {
        icon: 'dashboard-consults.svg',
        label: 'GP consults left',
        value: '0',
        detail: 'Shared pool of 10',
    },
    {
        icon: 'dashboard-renewal.svg',
        label: 'Renews in',
        value: '--',
        detail: '--',
    },
];

export default function Dashboard() {

    const {auth: {user}} = usePage().props

    return (
        <>
            <Head title="Dashboard" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title={`Welcome back, ${user.name}`}
                        description="Here's an overview of your sponsorship and family coverage."
                        action={
                            <Link
                                href={home()}
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
                        <ConsultationPoolCard />
                        <SubscriptionCard />
                    </section>

                    <section className="pt-4" aria-label="Recent activity">
                        <RecentActivityCard />
                    </section>
                </div>
            </PortalShell>
        </>
    );
}
