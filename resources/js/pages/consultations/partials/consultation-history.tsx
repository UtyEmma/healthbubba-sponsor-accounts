import { Link } from '@inertiajs/react';
import { StethoscopeIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import consultationsRoute from '@/routes/consultations';
import type {
    Consultation,
    ConsultationStatus,
    PaginatedConsultations,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function ConsultationHistory({
    consultations,
}: {
    consultations: PaginatedConsultations;
}) {
    return (
        <section className="pt-5" aria-labelledby="consultation-history-title">
            <Card className="overflow-hidden">
                <CardHeader className="border-b px-5 py-4 sm:px-6">
                    <CardTitle
                        id="consultation-history-title"
                        className="text-base"
                    >
                        Consultation history ({consultations.meta.total})
                    </CardTitle>
                </CardHeader>

                {consultations.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <div className="hidden overflow-x-auto md:block">
                            <Table className="min-w-[960px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">
                                            Beneficiary
                                        </TableHead>
                                        <TableHead>Consultation type</TableHead>
                                        <TableHead>Doctor</TableHead>
                                        <TableHead>Scheduled</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {consultations.data.map((consultation) => (
                                        <ConsultationRow
                                            key={consultation.id}
                                            consultation={consultation}
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="divide-y md:hidden">
                            {consultations.data.map((consultation) => (
                                <ConsultationCard
                                    key={consultation.id}
                                    consultation={consultation}
                                />
                            ))}
                        </div>
                    </>
                )}

                <ConsultationPagination consultations={consultations} />
            </Card>
        </section>
    );
}

function ConsultationRow({ consultation }: { consultation: Consultation }) {
    return (
        <TableRow>
            <TableCell className="h-16 pl-6 font-medium">
                <Person
                    name={consultation.beneficiary.name}
                    detail={consultation.beneficiary.email}
                />
            </TableCell>
            <TableCell>{consultation.consultationType.label}</TableCell>
            <TableCell>
                <Person
                    name={consultation.doctor?.name || 'Doctor unavailable'}
                    detail={consultation.doctor?.providerType}
                />
            </TableCell>
            <TableCell className="whitespace-nowrap text-muted-foreground">
                {formatDate(consultation.scheduledAt)}
            </TableCell>
            <TableCell>
                <StatusBadge
                    status={consultation.status.value}
                    label={consultation.status.label}
                />
            </TableCell>
            <TableCell className="max-w-56 text-sm text-muted-foreground">
                {consultation.cost.label}
            </TableCell>
        </TableRow>
    );
}

function ConsultationCard({ consultation }: { consultation: Consultation }) {
    return (
        <article className="grid gap-4 p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="truncate text-sm font-semibold">
                        {consultation.beneficiary.name || 'Unknown beneficiary'}
                    </h3>
                    <p className="truncate text-xs text-muted-foreground">
                        {consultation.beneficiary.email || 'No email available'}
                    </p>
                </div>
                <StatusBadge
                    status={consultation.status.value}
                    label={consultation.status.label}
                />
            </div>

            <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <Detail
                    label="Type"
                    value={consultation.consultationType.label}
                />
                <Detail
                    label="Scheduled"
                    value={formatDate(consultation.scheduledAt)}
                />
                <Detail
                    label="Doctor"
                    value={consultation.doctor?.name || 'Doctor unavailable'}
                />
                <Detail label="Cost" value={consultation.cost.label} />
            </dl>
        </article>
    );
}

function Person({ name, detail }: { name: string; detail?: string | null }) {
    return (
        <div className="min-w-0">
            <p className="truncate text-sm font-medium">
                {name || 'Not available'}
            </p>
            {detail && (
                <p className="truncate text-xs text-muted-foreground">
                    {detail}
                </p>
            )}
        </div>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 break-words">{value}</dd>
        </div>
    );
}

function StatusBadge({
    status,
    label,
}: {
    status: ConsultationStatus;
    label: string;
}) {
    const variant =
        status === 'completed'
            ? 'success'
            : status === 'cancelled'
              ? 'destructive'
              : 'warning';

    return <Badge variant={variant}>{label}</Badge>;
}

function EmptyState() {
    return (
        <CardContent className="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
            <span className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <StethoscopeIcon className="size-5" />
            </span>
            <h2 className="mt-4 text-base font-semibold">
                No sponsored consultations yet
            </h2>
            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                Appointments confirmed with this workspace's consultation
                allocation will appear here.
            </p>
        </CardContent>
    );
}

function ConsultationPagination({
    consultations,
}: {
    consultations: PaginatedConsultations;
}) {
    if (consultations.meta.last_page <= 1) {
        return null;
    }

    const previousPage = Math.max(1, consultations.meta.current_page - 1);
    const nextPage = Math.min(
        consultations.meta.last_page,
        consultations.meta.current_page + 1,
    );

    return (
        <nav
            className="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
            aria-label="Consultation history pagination"
        >
            <p className="text-sm text-muted-foreground">
                Showing {consultations.meta.from ?? 0}–
                {consultations.meta.to ?? 0} of {consultations.meta.total}
            </p>
            <div className="flex gap-2">
                {consultations.meta.current_page > 1 ? (
                    <Link
                        href={consultationsRoute.index({
                            query: { page: previousPage },
                        })}
                        preserveScroll
                        className={buttonVariants({
                            variant: 'outline',
                            size: 'compact',
                        })}
                    >
                        Previous
                    </Link>
                ) : (
                    <Button variant="outline" size="compact" disabled>
                        Previous
                    </Button>
                )}
                {consultations.meta.current_page <
                consultations.meta.last_page ? (
                    <Link
                        href={consultationsRoute.index({
                            query: { page: nextPage },
                        })}
                        preserveScroll
                        className={buttonVariants({
                            variant: 'outline',
                            size: 'compact',
                        })}
                    >
                        Next
                    </Link>
                ) : (
                    <Button variant="outline" size="compact" disabled>
                        Next
                    </Button>
                )}
            </div>
        </nav>
    );
}

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : 'Not scheduled';
}
