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
import type { ComponentType, SVGProps } from 'react';

import { PageHeader } from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import institutional from '@/routes/institutional';
import type {
    InstitutionalCampaignReportRow,
    InstitutionalReportsPageProps,
} from '@/types';

const reportIcons: Record<
    InstitutionalReportsPageProps['reports']['exports'][number]['type'],
    ComponentType<SVGProps<SVGSVGElement>>
> = {
    beneficiaries: UsersRoundIcon,
    coverage: ShieldPlusIcon,
    utilization: ActivityIcon,
    referrals: Share2Icon,
};

const exportFormats = [
    { value: 'print', label: 'PDF' },
    { value: 'xlsx', label: 'Excel' },
    { value: 'csv', label: 'CSV' },
] as const;

export default function InstitutionalReportsPage({
    reports,
}: InstitutionalReportsPageProps) {
    return (
        <>
            <Head title="Reports" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Reports"
                        description="Program reporting, community analytics, and impact measurement."
                    />

                    <Tabs defaultValue="reports" className="mt-4 flex-col">
                        <div className="overflow-x-auto pb-1">
                            <TabsList className="min-w-max">
                                <TabsTrigger value="reports">
                                    Reports
                                </TabsTrigger>
                                <TabsTrigger value="campaigns">
                                    By Campaign
                                </TabsTrigger>
                                <TabsTrigger value="analytics">
                                    Community Analytics
                                </TabsTrigger>
                                <TabsTrigger value="impact">Impact</TabsTrigger>
                            </TabsList>
                        </div>

                        <TabsContent value="reports">
                            <ReportExports exports={reports.exports} />
                        </TabsContent>
                        <TabsContent value="campaigns">
                            <CampaignReport rows={reports.byCampaign} />
                        </TabsContent>
                        <TabsContent value="analytics">
                            <CommunityReport rows={reports.community} />
                        </TabsContent>
                        <TabsContent value="impact">
                            <ImpactReport impact={reports.impact} />
                        </TabsContent>
                    </Tabs>
                </div>
            </DashboardLayout>
        </>
    );
}

function ReportExports({
    exports,
}: Pick<InstitutionalReportsPageProps['reports'], 'exports'>) {
    return (
        <div className="grid gap-4 pt-1 lg:grid-cols-2">
            {exports.map((report) => {
                const Icon = reportIcons[report.type];

                return (
                    <Card key={report.type}>
                        <CardContent className="flex min-h-24 flex-col gap-4 p-5 sm:flex-row sm:items-center">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-success-muted text-success">
                                <Icon className="size-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <h2 className="text-base font-medium">
                                    {report.title}
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    {report.description}
                                </p>
                            </div>
                            {report.available && report.type !== 'referrals' ? (
                                <div className="flex flex-wrap gap-1.5">
                                    {exportFormats.map((format) => (
                                        <a
                                            key={format.value}
                                            href={
                                                institutional.reports.export({
                                                    report: report.type,
                                                    format: format.value,
                                                }).url
                                            }
                                            target={
                                                format.value === 'print'
                                                    ? '_blank'
                                                    : undefined
                                            }
                                            rel="noreferrer"
                                            className={buttonVariants({
                                                variant: 'outline',
                                                size: 'compact',
                                            })}
                                        >
                                            {format.label}
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <span className="rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground">
                                    Unavailable
                                </span>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

function CampaignReport({ rows }: { rows: InstitutionalCampaignReportRow[] }) {
    return (
        <div className="space-y-4 pt-1">
            <Card className="overflow-hidden">
                <CardHeader className="p-4">
                    <CardTitle className="text-base">
                        Utilization by campaign
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Each campaign reports its own allocation, usage and
                        reach.
                    </p>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table className="min-w-[1050px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Campaign</TableHead>
                                <TableHead className="text-right">
                                    Scheduled consultations used
                                </TableHead>
                                <TableHead className="text-right">
                                    Instant consultations used
                                </TableHead>
                                <TableHead className="text-right">
                                    Medication
                                </TableHead>
                                <TableHead className="text-right">
                                    Allocated
                                </TableHead>
                                <TableHead className="text-right">
                                    Utilized
                                </TableHead>
                                <TableHead className="text-right">
                                    Remaining
                                </TableHead>
                                <TableHead className="text-right">
                                    People
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="h-28 text-center text-muted-foreground"
                                    >
                                        No campaign reporting data is available.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                rows.map((row) => (
                                    <TableRow key={row.slug}>
                                        <TableCell>
                                            <p className="font-medium">
                                                {row.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {row.location ?? '—'}
                                            </p>
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {row.gpUsed} / {row.gpAllocated}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {row.specialistUsed} /{' '}
                                            {row.specialistAllocated}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {formatMoney(row.medicationUsed)} /{' '}
                                            {formatMoney(
                                                row.medicationAllocated,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatMoney(row.allocated)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium text-blue-600">
                                            {formatMoney(row.utilized)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {row.remaining !== null
                                                ? formatMoney(row.remaining)
                                                : row.returned !== null
                                                  ? `${formatMoney(row.returned)} returned`
                                                  : '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {row.people}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>

            {rows.length > 0 && (
                <div className="grid gap-4 lg:grid-cols-3">
                    {rows
                        .filter((row) => row.remaining !== null)
                        .slice(0, 3)
                        .map((row) => (
                            <Card key={row.slug}>
                                <CardContent className="p-4">
                                    <h3 className="text-sm font-semibold">
                                        {row.name}
                                    </h3>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {row.people} enrolled ·{' '}
                                        {row.location ?? 'Location unavailable'}
                                    </p>
                                    <Progress
                                        value={row.utilizationPercentage}
                                        className="mt-3 h-1.5"
                                    />
                                    <p className="mt-3 text-xs text-muted-foreground">
                                        {formatMoney(row.utilized)} utilized of{' '}
                                        {formatMoney(row.allocated)} allocated
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                </div>
            )}
        </div>
    );
}

function CommunityReport({
    rows,
}: {
    rows: InstitutionalReportsPageProps['reports']['community'];
}) {
    const totalConsultations = Math.max(
        1,
        ...rows.map((row) => row.consultations),
    );

    return (
        <div className="space-y-4 pt-1">
            <Card>
                <CardHeader className="p-4">
                    <CardTitle className="text-base">
                        Sponsored consultations by community
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        Relative activity across the communities reached by your
                        campaigns.
                    </p>
                </CardHeader>
                <CardContent className="grid gap-3 px-4 pb-4">
                    {rows.length === 0 ? (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            No community data is available.
                        </p>
                    ) : (
                        rows.slice(0, 8).map((row) => (
                            <div
                                key={`${row.state}-${row.lga}-${row.community}`}
                                className="grid gap-2 sm:grid-cols-[180px_1fr_56px] sm:items-center"
                            >
                                <span className="truncate text-sm font-medium">
                                    {row.community}
                                </span>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-success"
                                        style={{
                                            width: `${Math.max(2, (row.consultations / totalConsultations) * 100)}%`,
                                        }}
                                    />
                                </div>
                                <span className="text-right text-xs text-muted-foreground">
                                    {row.consultations}
                                </span>
                            </div>
                        ))
                    )}
                </CardContent>
            </Card>

            <Card className="overflow-hidden">
                <div className="overflow-x-auto">
                    <Table className="min-w-[800px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>State</TableHead>
                                <TableHead>LGA / City</TableHead>
                                <TableHead>Ward</TableHead>
                                <TableHead>Community</TableHead>
                                <TableHead className="text-right">
                                    Beneficiaries
                                </TableHead>
                                <TableHead className="text-right">
                                    Consultations
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-28 text-center text-muted-foreground"
                                    >
                                        No community data is available.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                rows.map((row) => (
                                    <TableRow
                                        key={`${row.state}-${row.lga}-${row.community}`}
                                    >
                                        <TableCell>
                                            {row.state ?? '—'}
                                        </TableCell>
                                        <TableCell>{row.lga ?? '—'}</TableCell>
                                        <TableCell>{row.ward ?? '—'}</TableCell>
                                        <TableCell>{row.community}</TableCell>
                                        <TableCell className="text-right">
                                            {row.beneficiaries}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {row.consultations}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>
    );
}

function ImpactReport({
    impact,
}: Pick<InstitutionalReportsPageProps['reports'], 'impact'>) {
    const metrics = [
        {
            label: 'Reach',
            value: impact.reach.toLocaleString('en-NG'),
            icon: HeartPulseIcon,
            tone: 'green',
        },
        {
            label: 'Utilization',
            value: `${impact.utilizationPercentage}%`,
            icon: ActivityIcon,
            tone: 'blue',
        },
        {
            label: 'Funds deployed',
            value: formatMoney(impact.fundsDeployed),
            icon: WalletCardsIcon,
            tone: 'neutral',
        },
        {
            label: 'Confirmed consultations',
            value: impact.consultationsEnabled.toLocaleString('en-NG'),
            icon: TargetIcon,
            tone: 'green',
        },
    ] as const;

    return (
        <div className="space-y-4 pt-1">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {metrics.map((metric) => (
                    <ImpactMetric key={metric.label} {...metric} />
                ))}
            </div>
            <Card>
                <CardHeader className="p-5 pb-2">
                    <CardTitle className="text-base">
                        Outcome snapshot
                    </CardTitle>
                    <p className="text-xs text-muted-foreground">
                        High-level outcomes derived from confirmed program
                        activity.
                    </p>
                </CardHeader>
                <CardContent className="grid gap-4 p-5 pt-3 sm:grid-cols-2">
                    <div className="rounded-xl border p-4">
                        <p className="text-2xl font-semibold">
                            {impact.completionPercentage}%
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Consultation reservations confirmed
                        </p>
                    </div>
                    <div className="rounded-xl border p-4">
                        <p className="text-2xl font-semibold">
                            {formatMoney(impact.averageConsultationCost)}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Average confirmed consultation cost
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function ImpactMetric({
    label,
    value,
    icon: Icon,
    tone,
}: {
    label: string;
    value: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    tone: 'green' | 'blue' | 'neutral';
}) {
    return (
        <Card>
            <CardContent className="flex min-h-24 items-center gap-4 p-5">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl border bg-background">
                    <Icon
                        className={cn(
                            'size-5',
                            tone === 'green' && 'text-success',
                            tone === 'blue' && 'text-blue-600',
                        )}
                    />
                </span>
                <div className="min-w-0">
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="truncate text-lg font-semibold">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function formatMoney(value: string | number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value));
}
