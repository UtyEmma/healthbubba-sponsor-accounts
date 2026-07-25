import { Head } from '@inertiajs/react';
import {
    ArrowRightIcon,
    CalendarClockIcon,
    StethoscopeIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { BusinessMetricCard } from '@/pages/business-sponsor/partials/business-metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

import { ConsultationTrendsChart } from './partials/consultation-trends-chart';
import { institutionalNavigation } from './partials/institutional-navigation';

const activity = [
    {
        title: 'Bulk uploaded 45 beneficiaries (43 committed, 2 errors)',
        meta: 'Tobi Adeyinka · 20 Jun',
    },
    {
        title: 'Topped up GP coverage by 500 units',
        meta: 'Amaka Eze · 18 Jun',
    },
    {
        title: 'Created enrollment code HOPE-SABON-2026',
        meta: 'Amaka Eze · 15 Jun',
    },
];

export default function InstitutionalDashboard() {
    const [announcement, setAnnouncement] = useState('');

    return (
        <>
            <Head title="Hope Alive Foundation" />
            <BusinessPortalShell
                navigation={institutionalNavigation}
                navigationLabel="Institutional sponsor navigation"
            >
                <div className="mx-auto w-full max-w-6xl pb-4">
                    <PageHeader
                        title="Hope Alive Foundation"
                        description="Program reach and coverage utilization at a glance."
                        action={
                            <Button
                                size="compact"
                                onClick={() =>
                                    setAnnouncement(
                                        'Coverage management selected.',
                                    )
                                }
                            >
                                Manage Coverage
                            </Button>
                        }
                    />

                    <section
                        className="grid gap-4 pt-6 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Institutional coverage overview"
                    >
                        <BusinessMetricCard
                            label="Total beneficiaries"
                            value="6"
                            icon={UsersRoundIcon}
                            tone="green"
                        />
                        <BusinessMetricCard
                            label="Active beneficiaries"
                            value="5"
                            icon={UserRoundCheckIcon}
                            tone="blue"
                        />
                        <BusinessMetricCard
                            label="Coverage balance"
                            value="₦15,880,000"
                            icon={WalletCardsIcon}
                        />
                        <BusinessMetricCard
                            label="Coverage expires in"
                            value="265d"
                            icon={CalendarClockIcon}
                            tone="amber"
                        />
                    </section>

                    <section className="grid gap-4 pt-4 md:grid-cols-3">
                        <BusinessMetricCard
                            label="GP remaining"
                            value="1258"
                            icon={StethoscopeIcon}
                        />
                        <BusinessMetricCard
                            label="Specialist remaining"
                            value="332"
                            icon={StethoscopeIcon}
                        />
                        <BusinessMetricCard
                            label="Consultations completed"
                            value="3"
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
                                <ConsultationTrendsChart />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="gap-2 px-6 pt-6 pb-3">
                                <div className="flex items-center justify-between gap-4">
                                    <CardTitle className="text-base">
                                        Coverage utilization
                                    </CardTitle>
                                    <span className="text-xs font-medium text-success">
                                        Active
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Community Health Program 2026
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-5 px-6 pt-3">
                                <CoverageProgress
                                    label="GP coverage"
                                    value="1258 / 2000 left"
                                    progress={37}
                                />
                                <CoverageProgress
                                    label="Specialist coverage"
                                    value="332 / 500 left"
                                    progress={34}
                                />
                                <CoverageProgress
                                    label="Budget"
                                    value="15880000 / 25000000 left"
                                    progress={37}
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
                                {activity.map((item) => (
                                    <li
                                        key={item.title}
                                        className="flex items-start gap-3 text-sm"
                                    >
                                        <span className="mt-2 size-1.5 shrink-0 rounded-full bg-success" />
                                        <span>
                                            <span className="block">
                                                {item.title}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {item.meta}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>

                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
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
