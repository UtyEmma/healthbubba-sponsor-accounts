import { Head, router } from '@inertiajs/react';
import { InfoIcon } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { RosterPagination } from '@/components/roster-pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { DashboardLayout } from '@/layouts/dashboard';
import institutional from '@/routes/institutional';
import type { InstitutionalConsultationPageProps } from '@/types';

export default function InstitutionalConsultationsPage({
    consultations,
    campaigns,
    filters,
}: InstitutionalConsultationPageProps) {
    return (
        <>
            <Head title="Consultations" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl space-y-4">
                    <PageHeader
                        title="Consultations"
                        description="Every consultation, and the campaign that paid for it."
                    />

                    <div className="flex gap-3 rounded-xl border bg-muted/20 p-4">
                        <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-success-muted text-success">
                            <InfoIcon className="size-3.5" />
                        </span>
                        <div>
                            <p className="text-sm font-semibold">
                                Status only — no clinical data
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                You can see consultation activity and funding
                                source, but never diagnoses, case notes, or
                                prescriptions.
                            </p>
                        </div>
                    </div>

                    <Card className="overflow-hidden">
                        <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold">
                                    All consultations (
                                    {consultations.meta.total})
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Date, beneficiary, campaign, type, status,
                                    funding.
                                </p>
                            </div>
                            <select
                                className="h-10 rounded-control border border-input bg-background px-3 text-sm sm:w-40"
                                value={filters.campaign ?? ''}
                                onChange={(event) =>
                                    router.get(
                                        institutional.consultations.index().url,
                                        { campaign: event.target.value },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                            replace: true,
                                        },
                                    )
                                }
                            >
                                <option value="">All campaigns</option>
                                {campaigns.map((campaign) => (
                                    <option
                                        key={campaign.slug}
                                        value={campaign.slug}
                                    >
                                        {campaign.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <Table className="min-w-[820px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Beneficiary</TableHead>
                                        <TableHead>Campaign</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Payment source</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {consultations.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="h-28 text-center text-muted-foreground"
                                            >
                                                No consultation activity is
                                                available.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        consultations.data.map(
                                            (consultation) => (
                                                <TableRow key={consultation.id}>
                                                    <TableCell className="text-muted-foreground">
                                                        {formatDate(
                                                            consultation.date,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {consultation.beneficiary ||
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {consultation.campaign
                                                            ?.name ??
                                                            'Self-funded'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant="secondary"
                                                            className={
                                                                consultation.type ===
                                                                'specialist'
                                                                    ? 'bg-blue-50 text-blue-700'
                                                                    : undefined
                                                            }
                                                        >
                                                            {
                                                                consultation.typeLabel
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span
                                                            className={
                                                                consultation.status ===
                                                                'completed'
                                                                    ? 'text-success'
                                                                    : consultation.status ===
                                                                        'scheduled'
                                                                      ? 'text-primary'
                                                                      : 'text-muted-foreground'
                                                            }
                                                        >
                                                            {
                                                                consultation.statusLabel
                                                            }
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                consultation.paymentSource ===
                                                                'sponsor_coverage'
                                                                    ? 'success'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {
                                                                consultation.paymentSourceLabel
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        <RosterPagination pagination={consultations} />
                    </Card>
                </div>
            </DashboardLayout>
        </>
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}
