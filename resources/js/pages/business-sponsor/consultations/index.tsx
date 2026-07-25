import { Head } from '@inertiajs/react';
import {
    ActivityIcon,
    CreditCardIcon,
    DownloadIcon,
    StethoscopeIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { BusinessMetricCard } from '../partials/business-metric-card';
import { WorkforceStatusChart } from './partials/workforce-status-chart';

const reportCsv = encodeURIComponent(
    'Status,Employees\nActive,3\nInactive,1\nPending,1\nSuspended,1',
);

export default function BusinessConsultations() {
    return (
        <>
            <Head title="Reports" />
            <BusinessPortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Reports"
                        description="Utilization and coverage analytics for your organization."
                        action={
                            <a
                                href={`data:text/csv;charset=utf-8,${reportCsv}`}
                                download="swift-logistics-workforce-report.csv"
                                className={`${buttonVariants({ variant: 'outline', size: 'compact' })} self-start sm:self-auto`}
                            >
                                <DownloadIcon className="size-4" />
                                Export
                            </a>
                        }
                    />

                    <section
                        className="grid gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-3"
                        aria-label="Report overview"
                    >
                        <BusinessMetricCard
                            label="Active Employees"
                            value="6"
                            icon={UsersRoundIcon}
                            tone="green"
                        />
                        <BusinessMetricCard
                            label="GP consults left"
                            value="9"
                            icon={StethoscopeIcon}
                            tone="blue"
                        />
                        <BusinessMetricCard
                            label="Activation rate"
                            value="63%"
                            icon={ActivityIcon}
                        />
                    </section>

                    <section className="grid gap-5 pt-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Workforce status
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Employee lifecycle breakdown
                                </p>
                            </CardHeader>
                            <CardContent className="px-6 pt-5 pb-8">
                                <WorkforceStatusChart />
                            </CardContent>
                        </Card>

                        <Card className="self-start">
                            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                                <CardTitle className="text-base font-semibold">
                                    When allocations run out
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Care isn&apos;t blocked, beneficiaries
                                    unlock direct checkout via:
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-3 px-6 pt-3 pb-6">
                                <CheckoutMethod
                                    icon={WalletCardsIcon}
                                    title="Beneficiary wallet"
                                    description="Paid from their own balance"
                                />
                                <CheckoutMethod
                                    icon={CreditCardIcon}
                                    title="Card payment"
                                    description="Direct external checkout"
                                />
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </BusinessPortalShell>
        </>
    );
}

function CheckoutMethod({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof WalletCardsIcon;
    title: string;
    description: string;
}) {
    return (
        <div className="flex min-h-[62px] items-center gap-3 rounded-xl border border-border px-3">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-success-muted text-success">
                <Icon className="size-4" />
            </span>
            <div>
                <h3 className="text-sm font-medium">{title}</h3>
                <p className="text-xs text-muted-foreground">{description}</p>
            </div>
        </div>
    );
}
