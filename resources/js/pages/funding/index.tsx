import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Banknote,
    BriefcaseBusiness,
    Download,
    Info,
    Landmark,
    Plus,
    RotateCw,
    SlidersHorizontal,
    WalletCards,
} from 'lucide-react';
import { useState } from 'react';

import { PaymentStatusNotice } from '@/components/payment-status-notice';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import campaigns from '@/routes/campaigns';
import institutional from '@/routes/institutional';
import type {
    FundingCampaignAllocation,
    FundingLedgerEntry,
    FundingSummary,
    InstitutionalFundingPageProps,
} from '@/types';

import {
    EditCoverageRulesDialog,
    ExtendProgramDialog,
    FundAccountDialog,
} from './partials/funding-dialogs';

export default function InstitutionalFundingPage({
    funding,
}: InstitutionalFundingPageProps) {
    const { errors, flash } = usePage().props;
    const [fundOpen, setFundOpen] = useState(Boolean(errors.funding_payment));
    const [rulesOpen, setRulesOpen] = useState(
        Boolean(errors.coverage_type || errors.gp_limit_per_beneficiary),
    );
    const [extendOpen, setExtendOpen] = useState(Boolean(errors.months));

    return (
        <>
            <Head title="Funding" />
            <DashboardLayout>
                <div className="w-full max-w-[1120px]">
                    <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Funding
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Fund the institution wallet, then allocate to
                                campaigns. Allocated money is reserved, never
                                spent.
                            </p>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button
                                variant="outline"
                                onClick={() => setExtendOpen(true)}
                            >
                                <RotateCw className="size-4" />
                                Extend program
                            </Button>
                            <Button onClick={() => setFundOpen(true)}>
                                <Plus className="size-4" />
                                Fund account
                            </Button>
                        </div>
                    </header>

                    <PaymentStatusNotice
                        success={flash.success}
                        error={errors.payment ?? errors.funding_payment}
                    />

                    <SummaryCards summary={funding.summary} />

                    <div className="mt-4 flex gap-3 rounded-lg border border-information/20 bg-information/5 px-3 py-3 text-sm text-information">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        <p>
                            Available is money not yet allocated. Allocated is
                            reserved for live campaigns and still yours until
                            used. Utilized is care beneficiaries have actually
                            consumed. Booth setup and monthly service fees also
                            leave the wallet, but sit outside every campaign
                            allocation.
                        </p>
                    </div>

                    <Tabs defaultValue="allocation" className="mt-4 gap-4 flex-col">
                        <TabsList className="h-10 w-full justify-start overflow-x-auto sm:w-fit">
                            <TabsTrigger value="allocation" className="px-3">
                                Allocation
                            </TabsTrigger>
                            <TabsTrigger value="rules" className="px-3">
                                Rules
                            </TabsTrigger>
                            <TabsTrigger value="transactions" className="px-3">
                                Transactions ({funding.transactionCount})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="allocation" className="space-y-4">
                            <ProgramBalanceCard funding={funding} />
                            <CampaignAllocations
                                campaigns={funding.campaigns}
                                returned={funding.returnedFromEndedCampaigns}
                            />
                        </TabsContent>

                        <TabsContent value="rules" className="space-y-4">
                            <CoverageRules
                                funding={funding}
                                onEdit={() => setRulesOpen(true)}
                            />
                        </TabsContent>

                        <TabsContent value="transactions">
                            <WalletTransactions
                                entries={funding.transactions}
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </DashboardLayout>

            <FundAccountDialog
                open={fundOpen}
                onOpenChange={setFundOpen}
                summary={funding.summary}
            />
            <EditCoverageRulesDialog
                open={rulesOpen}
                onOpenChange={setRulesOpen}
                program={funding.program}
                configuration={funding.configuration}
            />
            <ExtendProgramDialog
                open={extendOpen}
                onOpenChange={setExtendOpen}
                program={funding.program}
            />
        </>
    );
}

function SummaryCards({ summary }: { summary: FundingSummary }) {
    const cards = [
        {
            label: 'Available balance',
            value: money(summary.availableBalance),
            note: 'Free to allocate',
            icon: WalletCards,
            iconClassName: 'bg-success-muted text-success',
        },
        {
            label: 'Allocated balance',
            value: money(summary.allocatedBalance),
            note: `Reserved by ${summary.allocatedCampaigns} ${summary.allocatedCampaigns === 1 ? 'campaign' : 'campaigns'}`,
            icon: BriefcaseBusiness,
            iconClassName: 'bg-information/10 text-information',
        },
        {
            label: 'Utilized',
            value: money(summary.utilized),
            note: 'Consumed by beneficiaries',
            icon: Activity,
            iconClassName: 'bg-muted text-foreground',
        },
        {
            label: 'Total funded',
            value: money(summary.totalFunded),
            note: `Wallet balance ${money(summary.walletBalance)}`,
            icon: Landmark,
            iconClassName: 'bg-muted text-foreground',
        },
    ];

    return (
        <section
            className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Funding summary"
        >
            {cards.map((card) => {
                const Icon = card.icon;

                return (
                    <Card
                        key={card.label}
                        className="border border-border shadow-none"
                    >
                        <CardContent className="flex items-center gap-3 p-4">
                            <span
                                className={cn(
                                    'grid size-10 shrink-0 place-items-center rounded-xl',
                                    card.iconClassName,
                                )}
                            >
                                <Icon className="size-5" />
                            </span>
                            <div className="min-w-0">
                                <p className="text-xs text-muted-foreground">
                                    {card.label}
                                </p>
                                <p className="mt-0.5 truncate text-xl font-semibold">
                                    {card.value}
                                </p>
                                <p className="mt-0.5 truncate text-xs text-subtle">
                                    {card.note}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </section>
    );
}

function ProgramBalanceCard({ funding }: InstitutionalFundingPageProps) {
    const available = Number(funding.summary.availableBalance);
    const allocated = Number(funding.summary.allocatedBalance);
    const total = Math.max(available + allocated, 1);

    return (
        <Card className="border border-border shadow-none">
            <CardContent className="p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-base font-semibold">
                                {funding.program.name}
                            </h2>
                            <StatusBadge
                                label={funding.program.statusLabel}
                                status={funding.program.status}
                            />
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {funding.program.coverageTypeLabel} ·{' '}
                            {date(funding.program.startsOn)} –{' '}
                            {date(funding.program.endsOn)}
                        </p>
                    </div>
                    <Link
                        href={institutional.reports.index()}
                        className="inline-flex h-9 items-center justify-center gap-2 self-start rounded-control border border-border px-3 text-sm font-medium hover:bg-accent"
                    >
                        <Download className="size-4" />
                        Report
                    </Link>
                </div>
                <div className="mt-4 flex items-center justify-between gap-4 text-sm font-medium">
                    <span>Wallet balance</span>
                    <strong>{money(funding.summary.walletBalance)}</strong>
                </div>
                <div
                    className="mt-3 flex h-3 overflow-hidden rounded-full bg-muted"
                    aria-label="Available and allocated wallet balance"
                >
                    <span
                        className="bg-success"
                        style={{ width: `${(available / total) * 100}%` }}
                    />
                    <span
                        className="bg-information"
                        style={{ width: `${(allocated / total) * 100}%` }}
                    />
                </div>
                <div className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-2">
                        <i className="size-2 rounded-full bg-success" />
                        Available {money(funding.summary.availableBalance)}
                    </span>
                    <span className="inline-flex items-center gap-2">
                        <i className="size-2 rounded-full bg-information" />
                        Allocated to campaigns{' '}
                        {money(funding.summary.allocatedBalance)}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function CampaignAllocations({
    campaigns: allocations,
    returned,
}: {
    campaigns: FundingCampaignAllocation[];
    returned: string;
}) {
    return (
        <>
            <Card className="overflow-hidden border border-border shadow-none">
                <div className="flex items-start justify-between gap-4 p-4 pb-3">
                    <div>
                        <h2 className="text-base font-semibold">
                            Allocation by campaign
                        </h2>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            What each campaign holds, has used and has left.
                        </p>
                    </div>
                    <Link
                        href={campaigns.index()}
                        className="inline-flex items-center gap-2 text-sm font-medium text-secondary"
                    >
                        Campaigns <ArrowRight className="size-4" />
                    </Link>
                </div>
                <div className="overflow-x-auto">
                    <Table className="min-w-[760px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="h-9 text-xs uppercase">
                                    Campaign
                                </TableHead>
                                <TableHead className="h-9 text-xs uppercase">
                                    Status
                                </TableHead>
                                <TableHead className="h-9 text-right text-xs uppercase">
                                    Allocated
                                </TableHead>
                                <TableHead className="h-9 text-right text-xs uppercase">
                                    Utilized
                                </TableHead>
                                <TableHead className="h-9 text-right text-xs uppercase">
                                    Reserved
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {allocations.map((campaign) => (
                                <TableRow key={campaign.id}>
                                    <TableCell className="h-16">
                                        <Link
                                            href={campaigns.show(campaign.slug)}
                                            className="font-medium hover:text-secondary"
                                        >
                                            {campaign.name}
                                        </Link>
                                        {campaign.location && (
                                            <div className="mt-1">
                                                <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                                    {campaign.location}
                                                </span>
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell className="h-16">
                                        <StatusBadge
                                            label={campaign.statusLabel}
                                            status={campaign.status}
                                        />
                                    </TableCell>
                                    <TableCell className="h-16 text-right font-medium">
                                        {money(campaign.allocated)}
                                    </TableCell>
                                    <TableCell className="h-16 text-right font-medium text-information">
                                        {money(campaign.utilized)}
                                    </TableCell>
                                    <TableCell className="h-16 text-right font-medium">
                                        {campaign.ended ? (
                                            <span className="text-muted-foreground">
                                                {money(campaign.returned)}{' '}
                                                returned
                                            </span>
                                        ) : (
                                            money(campaign.reserved)
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                            {allocations.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-28 text-center text-muted-foreground"
                                    >
                                        No campaign allocations yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>
            <Card className="border border-border shadow-none">
                <CardContent className="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center">
                    <div>
                        <h3 className="text-sm font-semibold">
                            Returned from ended campaigns
                        </h3>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Unused allocation released back into your available
                            balance.
                        </p>
                    </div>
                    <strong className="text-xl font-semibold text-success">
                        {money(returned)}
                    </strong>
                </CardContent>
            </Card>
        </>
    );
}

function CoverageRules({
    funding,
    onEdit,
}: InstitutionalFundingPageProps & { onEdit: () => void }) {
    const rules = [
        ['Coverage type', funding.program.coverageTypeLabel],
        ['GP limit / beneficiary', funding.program.gpLimitPerBeneficiary],
        [
            'Specialist limit / beneficiary',
            funding.program.specialistLimitPerBeneficiary,
        ],
        ['Daily usage limit', funding.program.dailyConsultationLimit],
        ['Expiry', funding.program.expiryCadenceLabel],
        ['Payment preference', funding.program.paymentPreferenceLabel],
    ];

    return (
        <>
            <Card className="border border-border shadow-none">
                <CardContent className="p-4">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div>
                            <h2 className="text-base font-semibold">
                                Coverage rules engine
                            </h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Defaults applied to every campaign unless
                                overridden.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" onClick={onEdit}>
                            <SlidersHorizontal className="size-4" /> Edit rules
                        </Button>
                    </div>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        {rules.map(([label, value]) => (
                            <div
                                key={String(label)}
                                className="rounded-lg border border-border px-3 py-3"
                            >
                                <dt className="text-xs text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="mt-1 text-sm font-medium">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </CardContent>
            </Card>
            <div className="flex gap-3 rounded-lg border border-border bg-muted/40 px-3 py-3 text-xs text-muted-foreground">
                <Info className="size-4 shrink-0" />
                <p>
                    Changing a rule applies to every campaign that has not
                    overridden it. Booth service fees are unaffected — they are
                    billed per booth, outside campaign allocation.
                </p>
            </div>
        </>
    );
}

function WalletTransactions({ entries }: { entries: FundingLedgerEntry[] }) {
    return (
        <Card className="overflow-hidden border border-border shadow-none">
            <div className="p-4 pb-3">
                <h2 className="text-base font-semibold">Wallet transactions</h2>
                <p className="mt-1 text-xs text-muted-foreground">
                    Funding in, and utilization out. Allocating to a campaign
                    does not appear here — it moves no money.
                </p>
            </div>
            <div className="overflow-x-auto">
                <Table className="min-w-[840px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="h-9 text-xs uppercase">
                                Date
                            </TableHead>
                            <TableHead className="h-9 text-xs uppercase">
                                Type
                            </TableHead>
                            <TableHead className="h-9 text-xs uppercase">
                                Description
                            </TableHead>
                            <TableHead className="h-9 text-xs uppercase">
                                Beneficiary
                            </TableHead>
                            <TableHead className="h-9 text-right text-xs uppercase">
                                Amount
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {entries.map((entry) => (
                            <TableRow key={entry.id}>
                                <TableCell className="h-12 whitespace-nowrap text-muted-foreground">
                                    {entry.date ? date(entry.date) : '—'}
                                </TableCell>
                                <TableCell className="h-12">
                                    <span
                                        className={cn(
                                            'rounded-full px-2 py-1 text-xs',
                                            entry.flow === 'credit'
                                                ? 'bg-success-muted text-success'
                                                : 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {entry.typeLabel}
                                    </span>
                                </TableCell>
                                <TableCell className="h-12 font-medium">
                                    {entry.description}
                                </TableCell>
                                <TableCell className="h-12 text-muted-foreground">
                                    {entry.beneficiary ?? '—'}
                                </TableCell>
                                <TableCell
                                    className={cn(
                                        'h-12 text-right font-medium whitespace-nowrap',
                                        entry.flow === 'credit' &&
                                            'text-success',
                                    )}
                                >
                                    {entry.flow === 'credit' ? '+' : '−'}
                                    {money(entry.amount)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {entries.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="h-32 text-center"
                                >
                                    <Banknote className="mx-auto size-6 text-muted-foreground" />
                                    <p className="mt-2 font-medium">
                                        No wallet transactions yet
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Verified funding and actual wallet
                                        spending will appear here.
                                    </p>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

function StatusBadge({ label, status }: { label: string; status: string }) {
    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                ['IN_PROGRESS', 'active'].includes(status) &&
                    'bg-success-muted text-success',
                status === 'PAUSED' && 'bg-warning/10 text-warning',
                ['PENDING', 'COMPLETED', 'ended'].includes(status) &&
                    'bg-muted text-muted-foreground',
            )}
        >
            {label}
        </span>
    );
}

function money(value: number | string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function date(value: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${value}T00:00:00Z`));
}
