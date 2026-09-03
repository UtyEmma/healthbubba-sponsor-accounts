import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    BriefcaseBusiness,
    CalendarDays,
    CreditCard,
    Megaphone,
    Plus,
    Store,
    UsersRound,
    WalletCards,
} from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
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
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import { FundAccountDialog } from '@/pages/funding/partials/funding-dialogs';
import campaigns from '@/routes/campaigns';
import institutional from '@/routes/institutional';
import type {
    FundingSummary,
    InstitutionalDashboardBooth,
    InstitutionalDashboardCampaign,
    InstitutionalDashboardPageProps,
    InstitutionalDashboardRemainingCampaign,
} from '@/types';

import { ConsultationTrendsChart } from './partials/consultation-trends-chart';

export default function InstitutionalDashboard({
    dashboard,
}: InstitutionalDashboardPageProps) {
    const { workspace, workspacePermissions } = usePage().props;
    const [fundingOpen, setFundingOpen] = useState(false);
    const fundingSummary: FundingSummary = {
        ...dashboard.funding,
        totalFunded: '0.00',
        walletBalance: dashboard.funding.availableBalance,
        allocatedCampaigns: dashboard.campaignPerformance.filter(
            (campaign) => Number(campaign.remaining) > 0,
        ).length,
    };

    return (
        <>
            <Head title={workspace.name} />
            <DashboardLayout>
                <main className="mx-auto w-full max-w-6xl space-y-4 pb-5">
                    <header className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {workspace.name}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Fund the wallet, allocate to campaigns, enroll
                                beneficiaries, track usage.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {workspacePermissions.canManage && (
                                <Button
                                    variant="outline"
                                    onClick={() => setFundingOpen(true)}
                                >
                                    <Plus className="size-4" /> Fund account
                                </Button>
                            )}
                            <Link
                                href={campaigns.index()}
                                className={buttonVariants()}
                            >
                                <Megaphone className="size-4" /> Campaigns
                            </Link>
                        </div>
                    </header>

                    <section
                        aria-label="Funding overview"
                        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <SummaryCard
                            icon={WalletCards}
                            tone="green"
                            label="Available balance"
                            value={money(
                                dashboard.funding.availableBalance,
                                dashboard.funding.currency,
                            )}
                            note="Not allocated to any campaign"
                        />
                        <SummaryCard
                            icon={BriefcaseBusiness}
                            tone="blue"
                            label="Allocated balance"
                            value={money(
                                dashboard.funding.allocatedBalance,
                                dashboard.funding.currency,
                            )}
                            note={`Reserved by ${fundingSummary.allocatedCampaigns} campaigns`}
                        />
                        <SummaryCard
                            icon={Activity}
                            label="Utilized"
                            value={money(
                                dashboard.funding.utilized,
                                dashboard.funding.currency,
                            )}
                            note="Actually used by beneficiaries"
                        />
                        <SummaryCard
                            icon={UsersRound}
                            label="Beneficiaries"
                            value={String(dashboard.beneficiaries.total)}
                            note={`${dashboard.beneficiaries.active} active`}
                        />
                    </section>

                    <BoothsSection
                        booths={dashboard.booths}
                        onFund={() => setFundingOpen(true)}
                        canFund={workspacePermissions.canManage}
                    />

                    <section className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
                        <CampaignPerformance
                            rows={dashboard.campaignPerformance}
                            currency={dashboard.funding.currency}
                        />
                        <ConsultationAllocation
                            consultations={dashboard.consultations}
                        />
                    </section>

                    <section className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
                        <Card className="border">
                            <CardHeader className="gap-1 pb-2">
                                <CardTitle className="text-base">
                                    Consultation trends
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Sponsored consultations completed per month.
                                </p>
                            </CardHeader>
                            <CardContent className="px-4 pt-0 pb-4">
                                <ConsultationTrendsChart
                                    data={dashboard.consultationTrends}
                                />
                            </CardContent>
                        </Card>

                        <Card className="border">
                            <CardHeader className="flex-row items-center justify-between gap-3 pb-3">
                                <CardTitle className="text-base">
                                    Recent activity
                                </CardTitle>
                                <Link
                                    href={institutional.notifications()}
                                    className="inline-flex items-center gap-1 text-sm font-medium text-success hover:underline"
                                >
                                    Notifications{' '}
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </CardHeader>
                            <CardContent className="pt-0">
                                {dashboard.activities.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No recent activity.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {dashboard.activities.map(
                                            (activity) => (
                                                <li
                                                    key={activity.id}
                                                    className="flex gap-3 text-sm"
                                                >
                                                    <span className="mt-2 size-1.5 shrink-0 rounded-full bg-success" />
                                                    <span className="min-w-0">
                                                        <span className="block leading-5">
                                                            {activity.title}
                                                        </span>
                                                        <span className="block text-xs text-muted-foreground">
                                                            {activity.actorName}{' '}
                                                            ·{' '}
                                                            {relativeDate(
                                                                activity.occurredAt,
                                                            )}
                                                        </span>
                                                    </span>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <Card className="border">
                        <CardHeader className="gap-1 pb-3">
                            <CardTitle className="text-base">
                                Consultations remaining by campaign
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Allocated units minus units used — what
                                beneficiaries can still draw on.
                            </p>
                        </CardHeader>
                        <CardContent className="grid gap-4 pt-0 md:grid-cols-2 xl:grid-cols-3">
                            {dashboard.remainingCampaigns.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No funded campaigns yet.
                                </p>
                            ) : (
                                dashboard.remainingCampaigns.map((campaign) => (
                                    <RemainingCampaignCard
                                        key={campaign.id}
                                        campaign={campaign}
                                    />
                                ))
                            )}
                        </CardContent>
                    </Card>
                </main>
            </DashboardLayout>

            <FundAccountDialog
                open={fundingOpen}
                onOpenChange={setFundingOpen}
                summary={fundingSummary}
            />
        </>
    );
}

function SummaryCard({
    icon: Icon,
    tone = 'neutral',
    label,
    value,
    note,
}: {
    icon: typeof WalletCards;
    tone?: 'green' | 'blue' | 'neutral';
    label: string;
    value: string;
    note: string;
}) {
    return (
        <Card className="border">
            <CardContent className="flex items-center gap-3 p-4">
                <span
                    className={cn(
                        'grid size-10 shrink-0 place-items-center rounded-xl border',
                        tone === 'green' &&
                            'border-success/20 bg-success-muted text-success',
                        tone === 'blue' &&
                            'border-information/20 bg-information/10 text-information',
                        tone === 'neutral' && 'bg-muted/50 text-foreground',
                    )}
                >
                    <Icon className="size-5" />
                </span>
                <span className="min-w-0">
                    <span className="block text-xs text-muted-foreground">
                        {label}
                    </span>
                    <strong className="block truncate text-lg font-semibold">
                        {value}
                    </strong>
                    <span className="block truncate text-xs text-subtle">
                        {note}
                    </span>
                </span>
            </CardContent>
        </Card>
    );
}

function BoothsSection({
    booths,
    onFund,
    canFund,
}: {
    booths: InstitutionalDashboardPageProps['dashboard']['booths'];
    onFund: () => void;
    canFund: boolean;
}) {
    const summary = booths.summary;

    return (
        <Card className="overflow-hidden border">
            <CardHeader className="flex-row items-start justify-between gap-4 pb-3">
                <div>
                    <CardTitle className="text-base">
                        Health Bubba Booths
                    </CardTitle>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Setup is one-time; the{' '}
                        {money(summary.serviceUnitFee, summary.currency)}{' '}
                        service fee is deducted monthly per active booth.
                    </p>
                </div>
                <Link
                    href={campaigns.index()}
                    className="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-success hover:underline"
                >
                    Manage <ArrowRight className="size-3.5" />
                </Link>
            </CardHeader>
            <CardContent className="grid gap-4 pt-0 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    icon={Store}
                    tone="green"
                    label="Active booths"
                    value={String(summary.operational)}
                    note={`${summary.awaitingDeployment} awaiting deployment`}
                />
                <SummaryCard
                    icon={CalendarDays}
                    tone="blue"
                    label="Monthly booth service cost"
                    value={money(summary.monthlyServiceCost, summary.currency)}
                    note="Active booths only"
                />
                <SummaryCard
                    icon={CreditCard}
                    label="Next deduction date"
                    value={formatDate(summary.nextDeduction)}
                    note={
                        summary.nextDeduction
                            ? daysUntil(summary.nextDeduction)
                            : 'No deduction scheduled'
                    }
                />
                <SummaryCard
                    icon={WalletCards}
                    label="Current wallet balance"
                    value={money(summary.walletBalance, summary.currency)}
                    note="Available to cover booth fees"
                />
            </CardContent>

            {summary.delinquentCount > 0 && (
                <div className="mx-4 mb-1 flex flex-col gap-2 rounded-lg border border-destructive/25 bg-destructive-muted px-3 py-2 text-sm text-destructive sm:flex-row sm:items-center">
                    <AlertTriangle className="size-4 shrink-0" />
                    <span>
                        {summary.delinquentCount}{' '}
                        {summary.delinquentCount === 1
                            ? 'booth is'
                            : 'booths are'}{' '}
                        in a billing grace period or suspended with{' '}
                        {money(summary.outstandingAmount, summary.currency)}{' '}
                        outstanding.
                    </span>
                    {canFund && (
                        <button
                            type="button"
                            onClick={onFund}
                            className="font-medium underline underline-offset-2 sm:ml-1"
                        >
                            Fund account
                        </button>
                    )}
                </div>
            )}

            <div className="overflow-x-auto">
                <Table className="min-w-[800px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>BOOTH</TableHead>
                            <TableHead>CAMPAIGN</TableHead>
                            <TableHead>STATUS</TableHead>
                            <TableHead>ACTIVATED</TableHead>
                            <TableHead className="text-right">
                                NEXT DEDUCTION
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {booths.rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No booths have been requested.
                                </TableCell>
                            </TableRow>
                        ) : (
                            booths.rows.map((booth) => (
                                <BoothRow key={booth.id} booth={booth} />
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

function BoothRow({ booth }: { booth: InstitutionalDashboardBooth }) {
    const overdue = booth.status === 'grace_period';

    return (
        <TableRow>
            <TableCell>
                <span className="block font-medium">{booth.name}</span>
                <span className="block text-xs text-muted-foreground">
                    {booth.community}
                </span>
            </TableCell>
            <TableCell>
                <Link
                    href={campaigns.show({ campaign: booth.campaignSlug })}
                    className="font-medium hover:underline"
                >
                    {booth.campaignName}
                </Link>
            </TableCell>
            <TableCell>
                <BoothStatus booth={booth} />
            </TableCell>
            <TableCell className="text-muted-foreground">
                {booth.activatedAt ? formatDate(booth.activatedAt) : 'Not yet'}
            </TableCell>
            <TableCell
                className={cn(
                    'text-right',
                    (overdue || booth.status === 'suspended') &&
                        'font-medium text-destructive',
                )}
            >
                <span className="block">
                    {booth.status === 'suspended'
                        ? 'Suspended'
                        : overdue
                          ? 'Overdue'
                          : booth.status === 'inactive' && booth.campaignEndsOn
                            ? `Stops ${formatDate(booth.campaignEndsOn)}`
                            : formatDate(booth.nextDeduction)}
                </span>
                {booth.outstandingAmount && (
                    <span className="block text-xs">
                        {money(booth.outstandingAmount, 'NGN')}
                    </span>
                )}
            </TableCell>
        </TableRow>
    );
}

function BoothStatus({ booth }: { booth: InstitutionalDashboardBooth }) {
    const variant =
        booth.status === 'active'
            ? 'success'
            : booth.status === 'grace_period'
              ? 'warning'
              : booth.status === 'suspended'
                ? 'destructive'
                : 'secondary';

    return <Badge variant={variant}>{booth.statusLabel}</Badge>;
}

function CampaignPerformance({
    rows,
    currency,
}: {
    rows: InstitutionalDashboardCampaign[];
    currency: string;
}) {
    return (
        <Card className="overflow-hidden border">
            <CardHeader className="flex-row items-start justify-between gap-4 pb-3">
                <div>
                    <CardTitle className="text-base">
                        Campaign performance
                    </CardTitle>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Allocation, utilization and reach per campaign.
                    </p>
                </div>
                <Link
                    href={campaigns.index()}
                    className="inline-flex items-center gap-1 text-sm font-medium text-success hover:underline"
                >
                    All campaigns <ArrowRight className="size-3.5" />
                </Link>
            </CardHeader>
            <div className="overflow-x-auto">
                <Table className="min-w-[650px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>CAMPAIGN</TableHead>
                            <TableHead className="text-right">
                                ALLOCATED
                            </TableHead>
                            <TableHead className="text-right">USED</TableHead>
                            <TableHead className="text-right">
                                REMAINING
                            </TableHead>
                            <TableHead className="text-right">PEOPLE</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="text-center text-muted-foreground"
                                >
                                    No campaigns yet.
                                </TableCell>
                            </TableRow>
                        ) : (
                            rows.map((campaign) => (
                                <TableRow key={campaign.id}>
                                    <TableCell>
                                        <Link
                                            href={campaigns.show({
                                                campaign: campaign.slug,
                                            })}
                                            className="block font-medium hover:underline"
                                        >
                                            {campaign.name}
                                        </Link>
                                        <CampaignStatus campaign={campaign} />
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {money(campaign.allocated, currency)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium text-information">
                                        {money(campaign.utilized, currency)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {money(campaign.remaining, currency)}
                                    </TableCell>
                                    <TableCell className="text-right text-muted-foreground">
                                        {campaign.people}
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

function CampaignStatus({
    campaign,
}: {
    campaign: InstitutionalDashboardCampaign;
}) {
    return (
        <Badge
            className="mt-1"
            variant={
                campaign.status === 'IN_PROGRESS'
                    ? 'success'
                    : campaign.status === 'PAUSED'
                      ? 'warning'
                      : 'secondary'
            }
        >
            {campaign.statusLabel}
        </Badge>
    );
}

function ConsultationAllocation({
    consultations,
}: {
    consultations: InstitutionalDashboardPageProps['dashboard']['consultations'];
}) {
    return (
        <Card className="border">
            <CardHeader className="gap-1 pb-3">
                <CardTitle className="text-base">
                    Consultation allocation
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    Across all live campaigns.
                </p>
            </CardHeader>
            <CardContent className="space-y-5 pt-0">
                <MetricProgress
                    label="GP consultations"
                    value={`${consultations.gp.remaining} / ${consultations.gp.allocated} left`}
                    progress={usedPercent(
                        consultations.gp.used + consultations.gp.reserved,
                        consultations.gp.allocated,
                    )}
                />
                <MetricProgress
                    label="Specialist consultations"
                    value={`${consultations.specialist.remaining} / ${consultations.specialist.allocated} left`}
                    progress={usedPercent(
                        consultations.specialist.used +
                            consultations.specialist.reserved,
                        consultations.specialist.allocated,
                    )}
                />
                <div className="overflow-hidden rounded-xl border text-sm">
                    <InfoRow
                        label="Allocated"
                        value={`${consultations.gp.allocated} GP`}
                    />
                    <InfoRow
                        label="Used"
                        value={String(consultations.gp.used)}
                    />
                    <InfoRow
                        label="Remaining"
                        value={String(consultations.gp.remaining)}
                        success
                    />
                </div>
            </CardContent>
        </Card>
    );
}

function InfoRow({
    label,
    value,
    success = false,
}: {
    label: string;
    value: string;
    success?: boolean;
}) {
    return (
        <div className="flex items-center justify-between border-b px-3 py-2 last:border-0">
            <span className="text-muted-foreground">{label}</span>
            <strong className={cn('font-semibold', success && 'text-success')}>
                {value}
            </strong>
        </div>
    );
}

function RemainingCampaignCard({
    campaign,
}: {
    campaign: InstitutionalDashboardRemainingCampaign;
}) {
    return (
        <div className="rounded-xl border p-4">
            <Link
                href={campaigns.show({ campaign: campaign.slug })}
                className="font-semibold hover:underline text-foreground"
            >
                {campaign.name}
            </Link>
            <div className="mt-4 space-y-4">
                <MetricProgress
                    label="GP"
                    value={`${campaign.gp.remaining} of ${campaign.gp.units} left`}
                    progress={usedPercent(
                        campaign.gp.confirmed + campaign.gp.reserved,
                        campaign.gp.units,
                    )}
                />
                <MetricProgress
                    label="Specialist"
                    value={`${campaign.specialist.remaining} of ${campaign.specialist.units} left`}
                    progress={usedPercent(
                        campaign.specialist.confirmed +
                            campaign.specialist.reserved,
                        campaign.specialist.units,
                    )}
                />
                <MetricProgress
                    label="Medication"
                    value={`${money(campaign.medication.remaining, campaign.currency)} left`}
                    progress={usedPercent(
                        Number(campaign.medication.used),
                        Number(campaign.medication.allocated),
                    )}
                />
            </div>
        </div>
    );
}

function MetricProgress({
    label,
    value,
    progress,
}: {
    label: string;
    value: string;
    progress: number;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-3 text-sm">
                <span className="font-medium">{label}</span>
                <span className="text-right text-muted-foreground">
                    {value}
                </span>
            </div>
            <Progress value={progress} aria-label={`${label}: ${value}`} />
        </div>
    );
}

function money(value: number | string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
}

function daysUntil(value: string): string {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(`${value}T00:00:00`);
    const days = Math.ceil((target.getTime() - today.getTime()) / 86_400_000);

    if (days < 0) {
        return 'Overdue';
    }

    if (days === 0) {
        return 'Due today';
    }

    return `In ${days} ${days === 1 ? 'day' : 'days'}`;
}

function relativeDate(value: string | null): string {
    if (!value) {
        return 'Recently';
    }

    const days = Math.max(
        0,
        Math.floor((Date.now() - new Date(value).getTime()) / 86_400_000),
    );

    if (days === 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'Yesterday';
    }

    return `${days} days ago`;
}

function usedPercent(used: number, allocated: number): number {
    if (allocated <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, (used / allocated) * 100));
}
