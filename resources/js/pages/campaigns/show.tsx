import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    CalendarDays,
    CircleSlash,
    Copy,
    CreditCard,
    Pause,
    Play,
    Plus,
    Search,
    Stethoscope,
    Store,
    Upload,
    UserPlus,
    Users,
    WalletCards,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import { index as campaignsIndex } from '@/actions/App/Http/Controllers/CampaignController';
import BillCampaignBoothController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/BillCampaignBoothController';
import DeactivateCampaignBoothController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/DeactivateCampaignBoothController';
import PauseCampaignController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/PauseCampaignController';
import ResumeCampaignController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/ResumeCampaignController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { DashboardLayout } from '@/layouts/dashboard';
import type {
    Campaign,
    CampaignDetailBooth,
    CampaignStatus,
    InstitutionalCampaignShowPageProps,
} from '@/types';
import {
    AddBoothDialog,
    AllocateMoreDialog,
    EndCampaignDialog,
    EnrollBeneficiaryDialog,
    FeeNotice,
    ImportBeneficiariesDialog,
    ImportResultDialog,
    RecordUsageDialog,
} from './partials/campaign-action-dialogs';

type Tab = 'allocation' | 'enrollment' | 'booths' | 'usage';
type Modal =
    | 'allocate'
    | 'usage'
    | 'enroll'
    | 'import'
    | 'booth'
    | 'end'
    | 'import-result'
    | null;
interface SharedProps {
    [key: string]: unknown;
    flash: { success?: string };
    workspacePermissions: { canManage: boolean };
}

export default function CampaignDetailsPage(
    props: InstitutionalCampaignShowPageProps,
) {
    const { campaign, detail, beneficiaries, importResult } = props;
    const { flash, workspacePermissions } = usePage<SharedProps>().props;
    const [tab, setTab] = useState<Tab>(
        importResult ? 'enrollment' : 'allocation',
    );
    const [modal, setModal] = useState<Modal>(
        importResult ? 'import-result' : null,
    );
    const canManage =
        workspacePermissions.canManage && campaign.status !== 'COMPLETED';
    const lifecycle = (action: 'pause' | 'resume') =>
        router.post(
            action === 'pause'
                ? PauseCampaignController.url(campaign.slug)
                : ResumeCampaignController.url(campaign.slug),
            {},
            { preserveScroll: true },
        );

    return (
        <>
            <Head title={campaign.name} />
            <DashboardLayout>
                <main className="mx-auto w-full max-w-6xl space-y-4 pb-10">
                    <Link
                        href={campaignsIndex.url()}
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        All campaigns
                    </Link>
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {campaign.name}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {campaign.description}
                            </p>
                            <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <StatusBadge
                                    status={campaign.status}
                                    label={campaign.statusLabel}
                                />
                                {campaign.location && (
                                    <span className="rounded-full bg-muted px-2.5 py-1">
                                        {campaign.location}
                                    </span>
                                )}
                                <span>{dateRange(campaign)}</span>
                            </div>
                        </div>
                        {canManage && (
                            <div className="flex flex-wrap gap-2">
                                {detail.capabilities.pause && (
                                    <Button
                                        variant="outline"
                                        onClick={() => lifecycle('pause')}
                                    >
                                        <Pause className="size-4" />
                                        Pause
                                    </Button>
                                )}
                                {detail.capabilities.resume && (
                                    <Button
                                        variant="outline"
                                        onClick={() => lifecycle('resume')}
                                    >
                                        <Play className="size-4" />
                                        Resume
                                    </Button>
                                )}
                                {detail.capabilities.end && (
                                    <Button
                                        variant="outline"
                                        onClick={() => setModal('end')}
                                    >
                                        <CircleSlash className="size-4" />
                                        End campaign
                                    </Button>
                                )}
                                {detail.capabilities.allocate && (
                                    <Button
                                        onClick={() => setModal('allocate')}
                                    >
                                        <Plus className="size-4" />
                                        Allocate more
                                    </Button>
                                )}
                            </div>
                        )}
                    </section>
                    {flash.success && (
                        <div className="rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                            {flash.success}
                        </div>
                    )}
                    <SummaryCards campaign={campaign} />
                    <nav
                        className="flex w-full gap-1 overflow-x-auto rounded-xl bg-muted p-1 sm:w-fit"
                        aria-label="Campaign details"
                    >
                        {(
                            [
                                ['allocation', 'Allocation'],
                                [
                                    'enrollment',
                                    `Enrollment (${detail.counts.enrollment})`,
                                ],
                                ['booths', `Booths (${detail.counts.booths})`],
                                ['usage', `Usage (${detail.counts.usage})`],
                            ] as const
                        ).map(([value, label]) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => setTab(value)}
                                className={`h-9 shrink-0 rounded-lg px-4 text-sm transition-colors ${tab === value ? 'bg-background font-medium text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}`}
                            >
                                {label}
                            </button>
                        ))}
                    </nav>
                    {tab === 'allocation' && (
                        <AllocationTab
                            {...props}
                            onRecord={() => setModal('usage')}
                            canManage={
                                canManage && detail.capabilities.recordUsage
                            }
                        />
                    )}
                    {tab === 'enrollment' && (
                        <EnrollmentTab
                            {...props}
                            canManage={canManage && detail.capabilities.enroll}
                            onEnroll={() => setModal('enroll')}
                            onImport={() => setModal('import')}
                            onBooth={() => {
                                setTab('booths');
                                setModal('booth');
                            }}
                        />
                    )}
                    {tab === 'booths' && (
                        <BoothsTab
                            {...props}
                            canManage={canManage}
                            onAdd={() => setModal('booth')}
                        />
                    )}
                    {tab === 'usage' && <UsageTab {...props} />}
                </main>
            </DashboardLayout>
            <AllocateMoreDialog
                campaign={campaign}
                detail={detail}
                open={modal === 'allocate'}
                onOpenChange={(open) => setModal(open ? 'allocate' : null)}
            />
            <RecordUsageDialog
                campaign={campaign}
                beneficiaries={beneficiaries.data}
                open={modal === 'usage'}
                onOpenChange={(open) => setModal(open ? 'usage' : null)}
            />
            <EnrollBeneficiaryDialog
                campaign={campaign}
                open={modal === 'enroll'}
                onOpenChange={(open) => setModal(open ? 'enroll' : null)}
            />
            <ImportBeneficiariesDialog
                campaign={campaign}
                open={modal === 'import'}
                onOpenChange={(open) => setModal(open ? 'import' : null)}
            />
            <AddBoothDialog
                campaign={campaign}
                detail={detail}
                open={modal === 'booth'}
                onOpenChange={(open) => setModal(open ? 'booth' : null)}
            />
            <EndCampaignDialog
                campaign={campaign}
                open={modal === 'end'}
                onOpenChange={(open) => setModal(open ? 'end' : null)}
            />
            {importResult && (
                <ImportResultDialog
                    campaign={campaign}
                    result={importResult}
                    open={modal === 'import-result'}
                    onOpenChange={(open) =>
                        setModal(open ? 'import-result' : null)
                    }
                />
            )}
        </>
    );
}

function SummaryCards({ campaign }: { campaign: Campaign }) {
    const financial = campaign.financial;
    const cards = [
        {
            label: 'Allocated',
            value: money(financial?.allocated),
            note: 'Reserved for this campaign',
            icon: WalletCards,
            tone: 'success',
        },
        {
            label: 'Utilized',
            value: money(financial?.utilized),
            note: `${financial?.utilizationPercentage ?? 0}% of allocation`,
            icon: Activity,
            tone: 'information',
        },
        {
            label: 'Remaining',
            value: money(financial?.reserved),
            note: 'Still reserved',
            icon: CreditCard,
            tone: 'neutral',
        },
        {
            label: 'Beneficiaries',
            value: String(campaign.beneficiaryCount ?? 0),
            note: `${campaign.activeBeneficiaryCount ?? 0} active`,
            icon: Users,
            tone: 'neutral',
        },
    ];

    return (
        <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {cards.map((card) => (
                <Card key={card.label}>
                    <CardContent className="flex items-center gap-3 p-4">
                        <span
                            className={`grid size-10 shrink-0 place-items-center rounded-xl ${card.tone === 'success' ? 'bg-success-muted text-success' : card.tone === 'information' ? 'bg-information/10 text-information' : 'bg-muted'}`}
                        >
                            <card.icon className="size-5" />
                        </span>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                {card.label}
                            </p>
                            <p className="text-lg font-semibold">
                                {card.value}
                            </p>
                            <p className="text-xs text-subtle">{card.note}</p>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </section>
    );
}

function AllocationTab({
    campaign,
    canManage,
    onRecord,
}: InstitutionalCampaignShowPageProps & {
    canManage: boolean;
    onRecord: () => void;
}) {
    const financial = campaign.financial;

    if (!financial) {
        return null;
    }

    const rows = [
        {
            label: 'GP consultations',
            note: `${money(financial.consultations.gp.unitFee)} each`,
            allocated: financial.consultations.gp.units,
            used: financial.consultations.gp.confirmed,
            remaining: financial.consultations.gp.remaining,
            value: money(
                financial.consultations.gp.remaining *
                    Number(financial.consultations.gp.unitFee),
            ),
        },
        {
            label: 'Specialist consultations',
            note: `${money(financial.consultations.specialist.unitFee)} each`,
            allocated: financial.consultations.specialist.units,
            used: financial.consultations.specialist.confirmed,
            remaining: financial.consultations.specialist.remaining,
            value: money(
                financial.consultations.specialist.remaining *
                    Number(financial.consultations.specialist.unitFee),
            ),
        },
        {
            label: 'Medication',
            note: 'budget',
            allocated: money(financial.budgets.medication.allocated),
            used: money(financial.budgets.medication.used),
            remaining: money(financial.budgets.medication.remaining),
            value: '—',
        },
        {
            label: 'Laboratory',
            note: 'budget',
            allocated: money(financial.budgets.laboratory.allocated),
            used: money(financial.budgets.laboratory.used),
            remaining: money(financial.budgets.laboratory.remaining),
            value: '—',
        },
    ];

    return (
        <section className="space-y-4">
            <Card className="overflow-hidden">
                <div className="flex items-start justify-between gap-4 px-4 py-4 sm:px-5">
                    <div>
                        <h2 className="text-base font-semibold">
                            Benefit lines
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Allocated, used and remaining for each benefit.
                        </p>
                    </div>
                    {canManage && (
                        <Button variant="outline" onClick={onRecord}>
                            <Stethoscope className="size-4" />
                            Record usage
                        </Button>
                    )}
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead className="bg-muted/25 text-xs text-muted-foreground">
                            <tr>
                                <Th>BENEFIT</Th>
                                <Th right>ALLOCATED</Th>
                                <Th right>USED</Th>
                                <Th right>REMAINING</Th>
                                <Th right>VALUE REMAINING</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.label} className="border-t">
                                    <td className="px-4 py-4">
                                        <strong className="font-medium">
                                            {row.label}
                                        </strong>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">
                                            {row.note}
                                        </span>
                                    </td>
                                    <Td>{row.allocated}</Td>
                                    <Td>{row.used}</Td>
                                    <Td>{row.remaining}</Td>
                                    <Td muted>{row.value}</Td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
            <div className="grid gap-3 sm:grid-cols-2">
                <ProgressCard
                    label="GP consultations"
                    text={`${financial.consultations.gp.remaining} / ${financial.consultations.gp.units} left`}
                    remaining={financial.consultations.gp.remaining}
                    allocated={financial.consultations.gp.units}
                />
                <ProgressCard
                    label="Specialist consultations"
                    text={`${financial.consultations.specialist.remaining} / ${financial.consultations.specialist.units} left`}
                    remaining={financial.consultations.specialist.remaining}
                    allocated={financial.consultations.specialist.units}
                />
                <ProgressCard
                    label="Medication budget"
                    text={`${money(financial.budgets.medication.remaining)} / ${money(financial.budgets.medication.allocated)} left`}
                    remaining={Number(financial.budgets.medication.remaining)}
                    allocated={Number(financial.budgets.medication.allocated)}
                />
                <ProgressCard
                    label="Laboratory budget"
                    text={`${money(financial.budgets.laboratory.remaining)} / ${money(financial.budgets.laboratory.allocated)} left`}
                    remaining={Number(financial.budgets.laboratory.remaining)}
                    allocated={Number(financial.budgets.laboratory.allocated)}
                />
            </div>
        </section>
    );
}

function ProgressCard({
    label,
    text,
    remaining,
    allocated,
}: {
    label: string;
    text: string;
    remaining: number;
    allocated: number;
}) {
    const used =
        allocated === 0
            ? 0
            : Math.max(
                  0,
                  Math.min(100, ((allocated - remaining) / allocated) * 100),
              );

    return (
        <Card>
            <CardContent className="p-4">
                <div className="mb-2 flex justify-between gap-4 text-sm">
                    <span className="font-medium">{label}</span>
                    <span className="text-muted-foreground">{text}</span>
                </div>
                <Progress value={used} className="h-2" />
            </CardContent>
        </Card>
    );
}

function EnrollmentTab({
    campaign,
    detail,
    beneficiaries,
    canManage,
    onEnroll,
    onImport,
    onBooth,
}: InstitutionalCampaignShowPageProps & {
    canManage: boolean;
    onEnroll: () => void;
    onImport: () => void;
    onBooth: () => void;
}) {
    const [search, setSearch] = useState('');
    const filtered = useMemo(
        () =>
            beneficiaries.data.filter((row) =>
                `${row.name} ${row.email} ${row.phone} ${row.community}`
                    .toLowerCase()
                    .includes(search.toLowerCase()),
            ),
        [beneficiaries.data, search],
    );
    const methods = [
        {
            icon: UserPlus,
            title: 'Manual enrollment',
            text: 'Add one beneficiary at a time.',
            action: 'Add beneficiary',
            click: onEnroll,
        },
        {
            icon: Upload,
            title: 'Excel / CSV upload',
            text: 'Bulk enroll from a spreadsheet.',
            action: 'Upload file',
            click: onImport,
        },
        {
            icon: Store,
            title: 'Health Bubba Booth',
            text: 'On-site enrollment by booth officers.',
            action: 'Add booth',
            click: onBooth,
        },
    ];

    return (
        <section className="space-y-4">
            <div className="grid gap-3 md:grid-cols-3">
                {methods.map((method) => (
                    <Card key={method.title}>
                        <CardContent className="p-4">
                            <span className="mb-3 grid size-10 place-items-center rounded-xl bg-success-muted text-success">
                                <method.icon className="size-5" />
                            </span>
                            <h2 className="text-sm font-semibold">
                                {method.title}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {method.text}
                            </p>
                            {canManage && (
                                <Button
                                    className="mt-3"
                                    variant="outline"
                                    onClick={method.click}
                                >
                                    {method.action}
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
            <Card className="overflow-hidden">
                <div className="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-base font-semibold">
                            Enrollment list ({detail.counts.enrollment})
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Everyone covered under {campaign.name}.
                        </p>
                    </div>
                    <label className="relative">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            className="w-full pl-9 sm:w-64"
                            placeholder="Search this campaign..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </label>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead className="bg-muted/25 text-xs text-muted-foreground">
                            <tr>
                                <Th>BENEFICIARY</Th>
                                <Th>CONTACT</Th>
                                <Th>COMMUNITY</Th>
                                <Th>ENROLLED VIA</Th>
                                <Th>STATUS</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((row) => (
                                <tr key={row.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <span className="mr-3 inline-grid size-9 place-items-center rounded-full bg-muted text-xs">
                                            {initials(row.name)}
                                        </span>
                                        <span className="font-medium">
                                            {row.name}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        <span className="block">
                                            {row.email}
                                        </span>
                                        <span>{row.phone}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                            {row.community ?? '—'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground capitalize">
                                        {row.source.replace('_', ' ')}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                row.status === 'active'
                                                    ? 'success'
                                                    : 'secondary'
                                            }
                                        >
                                            {row.status}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
            {detail.enrollmentCode && (
                <Card>
                    <CardContent className="p-4">
                        <h2 className="text-base font-semibold">
                            Enrollment codes
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Display-only campaign enrollment identifier.
                        </p>
                        <div className="mt-3 max-w-lg rounded-xl border p-3">
                            <div className="flex items-center justify-between gap-3 text-sm font-semibold">
                                <span>{detail.enrollmentCode}</span>
                                <button
                                    type="button"
                                    aria-label="Copy enrollment code"
                                    onClick={() =>
                                        navigator.clipboard.writeText(
                                            detail.enrollmentCode ?? '',
                                        )
                                    }
                                >
                                    <Copy className="size-4 text-muted-foreground" />
                                </button>
                            </div>
                            <div className="mt-3 flex justify-between text-sm">
                                <span>Enrolled</span>
                                <span>
                                    {detail.counts.enrollment} /{' '}
                                    {campaign.estimatedBeneficiaries ?? '∞'}
                                </span>
                            </div>
                            <Progress
                                className="mt-2 h-2"
                                value={
                                    campaign.estimatedBeneficiaries
                                        ? Math.min(
                                              100,
                                              (detail.counts.enrollment /
                                                  campaign.estimatedBeneficiaries) *
                                                  100,
                                          )
                                        : 0
                                }
                            />
                        </div>
                    </CardContent>
                </Card>
            )}
        </section>
    );
}

function BoothsTab({
    campaign,
    detail,
    canManage,
    onAdd,
}: InstitutionalCampaignShowPageProps & {
    canManage: boolean;
    onAdd: () => void;
}) {
    const active = detail.booths.filter((booth) => booth.status === 'active');
    const operational = detail.booths.filter((booth) =>
        ['active', 'grace_period'].includes(booth.status),
    );
    const monthly = active.reduce(
        (total, booth) => total + Number(booth.monthlyFee),
        0,
    );
    const next = active
        .map((booth) => booth.nextDeduction)
        .filter((date): date is string => Boolean(date))
        .sort()[0];

    return (
        <section className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <MiniMetric
                    icon={Store}
                    label="Active booths"
                    value={String(operational.length)}
                    note={`${detail.booths.filter((booth) => booth.status === 'requested').length} awaiting deployment`}
                />
                <MiniMetric
                    icon={CalendarDays}
                    label="Monthly booth service cost"
                    value={money(monthly)}
                    note={`${active.length} booths`}
                />
                <MiniMetric
                    icon={CreditCard}
                    label="Next deduction"
                    value={next ? formatDate(next) : '—'}
                    note={
                        next ? money(active[0]?.monthlyFee) : 'No due service'
                    }
                />
                <MiniMetric
                    icon={WalletCards}
                    label="Wallet balance"
                    value={money(detail.configuration.walletBalance)}
                    note="Available for booth fees"
                />
            </div>
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-semibold">
                    Booths on this campaign ({detail.booths.length})
                </h2>
                {canManage && detail.capabilities.addBooths && (
                    <Button variant="outline" onClick={onAdd}>
                        <Plus className="size-4" />
                        Add booths
                    </Button>
                )}
            </div>
            {detail.booths.map((booth) => (
                <BoothCard
                    key={booth.id}
                    campaign={campaign}
                    booth={booth}
                    canManage={canManage}
                />
            ))}
            {detail.booths.length === 0 && (
                <Card>
                    <CardContent className="py-10 text-center text-sm text-muted-foreground">
                        No booths have been requested for this campaign.
                    </CardContent>
                </Card>
            )}
            <FeeNotice>
                Booth fees are separate from this campaign&apos;s healthcare
                allocation. Consultation, medication and laboratory allocations
                fund care; booth fees cover the physical access point and the
                team running it.
            </FeeNotice>
        </section>
    );
}

function MiniMetric({
    icon: Icon,
    label,
    value,
    note,
}: {
    icon: typeof Store;
    label: string;
    value: string;
    note: string;
}) {
    return (
        <Card>
            <CardContent className="flex gap-3 p-4">
                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-muted">
                    <Icon className="size-5" />
                </span>
                <div>
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <strong className="text-lg">{value}</strong>
                    <p className="text-xs text-subtle">{note}</p>
                </div>
            </CardContent>
        </Card>
    );
}
function BoothCard({
    campaign,
    booth,
    canManage,
}: {
    campaign: Campaign;
    booth: CampaignDetailBooth;
    canManage: boolean;
}) {
    const bill = () =>
        router.post(
            BillCampaignBoothController.url({
                campaign: campaign.slug,
                booth: booth.id,
            }),
            {},
            { preserveScroll: true },
        );
    const deactivate = () =>
        router.delete(
            DeactivateCampaignBoothController.url({
                campaign: campaign.slug,
                booth: booth.id,
            }),
            { preserveScroll: true },
        );

    return (
        <Card>
            <CardContent className="p-4">
                <div className="flex flex-col justify-between gap-3 sm:flex-row">
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="text-base font-semibold">
                                {booth.name}
                            </h3>
                            <Badge
                                variant={
                                    booth.status === 'active'
                                        ? 'success'
                                        : booth.status === 'grace_period'
                                          ? 'warning'
                                          : booth.status === 'suspended'
                                            ? 'destructive'
                                            : booth.status === 'requested'
                                              ? 'warning'
                                              : 'secondary'
                                }
                            >
                                {booth.statusLabel}
                            </Badge>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {booth.community} · {booth.contactName} ·{' '}
                            {booth.contactPhone}
                        </p>
                    </div>
                    <div className="text-left sm:text-right">
                        <strong className="text-sm">
                            {money(booth.monthlyFee)} / month
                        </strong>
                        <p className="text-xs text-muted-foreground">
                            Setup {money(booth.setupFee)} paid
                        </p>
                    </div>
                </div>
                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <InfoCell
                        label="Activated"
                        value={
                            booth.activatedAt
                                ? formatDate(booth.activatedAt)
                                : 'Awaiting deployment'
                        }
                    />
                    <InfoCell
                        label="Next deduction"
                        value={
                            booth.nextDeduction
                                ? formatDate(booth.nextDeduction)
                                : '—'
                        }
                    />
                    <InfoCell
                        label="Paid through"
                        value={
                            booth.paidThrough
                                ? formatDate(booth.paidThrough)
                                : '—'
                        }
                    />
                    <InfoCell
                        label="Enrolled on-site"
                        value={`${booth.enrolledOnSite ?? 0} / ${booth.expectedBeneficiaries ?? '—'}`}
                    />
                </div>
                {canManage &&
                    ['active', 'grace_period', 'suspended'].includes(
                        booth.status,
                    ) && (
                        <div className="mt-4 flex flex-wrap gap-2 border-t pt-3">
                            <Button variant="outline" onClick={bill}>
                                <CreditCard className="size-4" />
                                Run monthly deduction
                            </Button>
                            <Button variant="outline" onClick={deactivate}>
                                <CircleSlash className="size-4" />
                                Request deactivation
                            </Button>
                        </div>
                    )}
                {booth.outstandingAmount && (
                    <p className="mt-3 text-sm font-medium text-destructive">
                        {money(booth.outstandingAmount)} outstanding
                        {booth.billingGraceEndsOn
                            ? ` · grace ends ${formatDate(booth.billingGraceEndsOn)}`
                            : ''}
                    </p>
                )}
                <p className="mt-3 text-xs text-subtle">
                    {booth.paidPeriods} monthly service fees charged to date.
                </p>
            </CardContent>
        </Card>
    );
}
function InfoCell({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border p-3 text-sm">
            <span className="block text-xs text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

function UsageTab({
    detail,
    consultations,
}: InstitutionalCampaignShowPageProps) {
    return (
        <section className="space-y-4">
            <Card className="overflow-hidden">
                <div className="p-4">
                    <h2 className="text-base font-semibold">Campaign ledger</h2>
                    <p className="text-sm text-muted-foreground">
                        Allocations, top-ups, utilization, refunds, booth
                        charges and operating costs.
                    </p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead className="bg-muted/25 text-xs text-muted-foreground">
                            <tr>
                                <Th>DATE</Th>
                                <Th>TYPE</Th>
                                <Th>BENEFIT</Th>
                                <Th>BENEFICIARY</Th>
                                <Th right>QTY</Th>
                                <Th right>AMOUNT</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {detail.ledger.map((entry) => (
                                <tr key={entry.id} className="border-t">
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {entry.date
                                            ? shortDate(entry.date)
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                entry.type === 'utilization'
                                                    ? 'destructive'
                                                    : entry.type ===
                                                        'campaign_refund'
                                                      ? 'success'
                                                      : 'secondary'
                                            }
                                        >
                                            {entry.label}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {entry.benefit}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {entry.beneficiary ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {entry.quantity ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right font-medium">
                                        {moneySigned(entry.amount)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {detail.ledger.length === 0 && (
                        <p className="p-6 text-center text-sm text-muted-foreground">
                            No campaign ledger activity yet.
                        </p>
                    )}
                </div>
            </Card>
            <Card className="overflow-hidden">
                <div className="p-4">
                    <h2 className="text-base font-semibold">
                        Consultations ({consultations.meta.total})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Sponsored consultation activity — status only, never
                        clinical data.
                    </p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[600px] text-sm">
                        <thead className="bg-muted/25 text-xs text-muted-foreground">
                            <tr>
                                <Th>DATE</Th>
                                <Th>BENEFICIARY</Th>
                                <Th>TYPE</Th>
                                <Th>STATUS</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {consultations.data.map((row) => (
                                <tr
                                    key={`${row.id}-${row.createdAt}`}
                                    className="border-t"
                                >
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {row.scheduledAt
                                            ? shortDate(row.scheduledAt)
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {row.beneficiary.name || 'Beneficiary'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="secondary">
                                            {row.consultationType.label}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {row.status.label}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </section>
    );
}

function Th({
    children,
    right = false,
}: {
    children: React.ReactNode;
    right?: boolean;
}) {
    return (
        <th
            className={`px-4 py-2 font-medium ${right ? 'text-right' : 'text-left'}`}
        >
            {children}
        </th>
    );
}
function Td({
    children,
    muted = false,
}: {
    children: React.ReactNode;
    muted?: boolean;
}) {
    return (
        <td
            className={`px-4 py-4 text-right ${muted ? 'text-muted-foreground' : ''}`}
        >
            {children}
        </td>
    );
}
function StatusBadge({
    status,
    label,
}: {
    status: CampaignStatus;
    label: string;
}) {
    return (
        <Badge
            variant={
                status === 'IN_PROGRESS'
                    ? 'success'
                    : status === 'COMPLETED'
                      ? 'secondary'
                      : 'warning'
            }
        >
            {label}
        </Badge>
    );
}
function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}
function dateRange(campaign: Campaign): string {
    return campaign.startDate && campaign.endDate
        ? `${formatDate(campaign.startDate)} – ${formatDate(campaign.endDate)}`
        : '';
}
function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(value));
}
function shortDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        timeZone: 'UTC',
    }).format(new Date(value));
}
function money(value?: string | number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}
function moneySigned(value: string): string {
    const number = Number(value);

    return `${number > 0 ? '+' : number < 0 ? '−' : ''}${money(Math.abs(number))}`;
}
