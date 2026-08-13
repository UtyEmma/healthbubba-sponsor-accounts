import { Head } from '@inertiajs/react';
import {
    ActivityIcon,
    HeartPulseIcon,
    Share2Icon,
    ShieldPlusIcon,
    TargetIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { BusinessMetricCard } from '@/pages/business-sponsor/partials/business-metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { institutionalNavigation } from '../partials/institutional-navigation';
import { CommunityChart } from './partials/community-chart';
import { DashboardLayout } from '@/layouts/dashboard';

const reportCards = [
    ['Beneficiary Report', 'Roster, statuses, communities', UsersRoundIcon],
    ['Coverage Report', 'Purchased, consumed, remaining', ShieldPlusIcon],
    ['Utilization Report', 'Consultation usage over time', ActivityIcon],
    ['Referral Report', 'Referral cases & outcomes', Share2Icon],
] as const;

const communities = [
    ['Kano', 'Fagge', 'Sabon Gari East', 'Sabon Gari', '134', '312'],
    ['Enugu', 'Enugu North', 'Ogui', 'Ogui', '150', '287'],
    ['Kano', 'Nassarawa', 'Tudun Wada', 'Tudun Wada', '61', '143'],
];

export default function InstitutionalReportsPage() {
    const [announcement, setAnnouncement] = useState('');
    return (
        <>
            <Head title="Reports" />
            <DashboardLayout >
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Reports"
                        description="Program reporting, community analytics, and impact measurement."
                    />
                    <Tabs defaultValue="reports" className="pt-6">
                        <TabsList>
                            <TabsTrigger value="reports">Reports</TabsTrigger>
                            <TabsTrigger value="analytics">
                                Community Analytics
                            </TabsTrigger>
                            <TabsTrigger value="impact">Impact</TabsTrigger>
                        </TabsList>
                        <TabsContent value="reports">
                            <div className="grid gap-4 pt-1 lg:grid-cols-2">
                                {reportCards.map(
                                    ([title, description, Icon]) => (
                                        <Card key={title}>
                                            <CardContent className="flex min-h-[87px] items-center gap-3 p-5">
                                                <span className="flex size-11 items-center justify-center rounded-xl bg-success-muted text-success">
                                                    <Icon className="size-5" />
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <h2 className="font-medium">
                                                        {title}
                                                    </h2>
                                                    <p className="text-xs text-muted-foreground">
                                                        {description}
                                                    </p>
                                                </div>
                                                <div className="flex gap-1">
                                                    {[
                                                        'PDF',
                                                        'Excel',
                                                        'CSV',
                                                    ].map((format) => (
                                                        <Button
                                                            key={format}
                                                            variant="outline"
                                                            size="compact"
                                                            onClick={() =>
                                                                setAnnouncement(
                                                                    `${title} ${format} export selected.`,
                                                                )
                                                            }
                                                        >
                                                            {format}
                                                        </Button>
                                                    ))}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ),
                                )}
                            </div>
                        </TabsContent>
                        <TabsContent value="analytics">
                            <Card className="mt-1">
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Consultations by community
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="px-5 pb-5">
                                    <CommunityChart />
                                </CardContent>
                            </Card>
                            <Card className="mt-4 overflow-hidden">
                                <CardContent className="overflow-x-auto p-0">
                                    <Table className="min-w-[800px]">
                                        <TableHeader>
                                            <TableRow>
                                                {[
                                                    'State',
                                                    'LGA',
                                                    'Ward',
                                                    'Community',
                                                    'Beneficiaries',
                                                    'Consultations',
                                                ].map((h) => (
                                                    <TableHead key={h}>
                                                        {h}
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {communities.map((row) => (
                                                <TableRow key={row[3]}>
                                                    {row.map((cell, index) => (
                                                        <TableCell
                                                            key={`${row[3]}-${index}`}
                                                            className="h-[53px] text-sm"
                                                        >
                                                            {index > 3 ? (
                                                                <span className="block text-right">
                                                                    {cell}
                                                                </span>
                                                            ) : (
                                                                cell
                                                            )}
                                                        </TableCell>
                                                    ))}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>
                        <TabsContent value="impact">
                            <div className="grid gap-4 pt-1 sm:grid-cols-2 xl:grid-cols-4">
                                <BusinessMetricCard
                                    label="Reach"
                                    value="345"
                                    icon={HeartPulseIcon}
                                    tone="green"
                                />
                                <BusinessMetricCard
                                    label="Utilization"
                                    value="37%"
                                    icon={ActivityIcon}
                                    tone="blue"
                                />
                                <BusinessMetricCard
                                    label="Funds deployed"
                                    value="₦9,120,000"
                                    icon={WalletCardsIcon}
                                />
                                <BusinessMetricCard
                                    label="Consultations enabled"
                                    value="742"
                                    icon={TargetIcon}
                                    tone="green"
                                />
                            </div>
                            <Card className="mt-4">
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Outcome snapshot
                                    </CardTitle>
                                    <p className="text-sm text-muted-foreground">
                                        High-level program outcomes
                                    </p>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    {[
                                        ['92%', 'Consultations completed'],
                                        [
                                            '₦12,291',
                                            'Avg. cost per consultation',
                                        ],
                                        ['1', 'Referrals completed'],
                                    ].map(([v, l]) => (
                                        <Card key={l}>
                                            <CardContent className="p-4">
                                                <p className="text-2xl font-semibold">
                                                    {v}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {l}
                                                </p>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </DashboardLayout>
        </>
    );
}
