import { Link } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type {
    MedicalAccessRequest,
    MedicalAccessRequestStatus,
    PaginatedMedicalAccessRequests,
} from '@/types';

const statusVariants: Record<
    MedicalAccessRequestStatus,
    'success' | 'warning' | 'destructive' | 'secondary'
> = {
    approved: 'success',
    pending: 'warning',
    denied: 'destructive',
    expired: 'secondary',
};

export function AccessRequestsTable({
    requests,
}: {
    requests: PaginatedMedicalAccessRequests;
}) {
    return (
        <Card>
            <CardHeader className="gap-1 px-6 py-4">
                <CardTitle className="text-base leading-6 font-semibold">
                    Access requests
                </CardTitle>
                <CardDescription className="text-sm leading-5">
                    Consent requests expire after 24 hours. Approved access is
                    recorded for 30 days; viewing medical records is not yet
                    available.
                </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
                {requests.data.length === 0 ? (
                    <div className="border-t px-6 py-12 text-center">
                        <p className="text-sm font-medium">
                            No medical access requests yet
                        </p>
                        <p className="pt-1 text-sm text-muted-foreground">
                            New requests and beneficiary decisions will appear
                            here.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="grid gap-3 border-t p-4 md:hidden">
                            {requests.data.map((request) => (
                                <RequestCard
                                    key={request.publicId}
                                    request={request}
                                />
                            ))}
                        </div>
                        <div className="hidden overflow-x-auto border-t md:block">
                            <Table className="min-w-[1100px]">
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-6">
                                            Beneficiary
                                        </TableHead>
                                        <TableHead>Data type</TableHead>
                                        <TableHead className="max-w-64">
                                            Reason
                                        </TableHead>
                                        <TableHead>Requested</TableHead>
                                        <TableHead>Review deadline</TableHead>
                                        <TableHead>Access expires</TableHead>
                                        <TableHead className="pr-6">
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {requests.data.map((request) => (
                                        <TableRow key={request.publicId}>
                                            <TableCell className="h-[72px] pl-6">
                                                <p className="font-medium">
                                                    {request.beneficiary.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {request.beneficiary.email}
                                                </p>
                                            </TableCell>
                                            <TableCell className="h-[72px] text-muted-foreground">
                                                {request.dataType.label}
                                            </TableCell>
                                            <TableCell className="h-[72px] max-w-64 text-muted-foreground">
                                                <span className="line-clamp-2">
                                                    {request.reason ?? '—'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="h-[72px] text-muted-foreground">
                                                {formatDate(
                                                    request.requestedAt,
                                                )}
                                            </TableCell>
                                            <TableCell className="h-[72px] text-muted-foreground">
                                                {formatDate(
                                                    request.reviewExpiresAt,
                                                )}
                                            </TableCell>
                                            <TableCell className="h-[72px] text-muted-foreground">
                                                {request.accessExpiresAt
                                                    ? formatDate(
                                                          request.accessExpiresAt,
                                                      )
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="h-[72px] pr-6">
                                                <StatusBadge
                                                    status={request.status}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                )}
                <MedicalAccessPagination pagination={requests} />
            </CardContent>
        </Card>
    );
}

function RequestCard({ request }: { request: MedicalAccessRequest }) {
    return (
        <article className="grid gap-4 rounded-xl border bg-card p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{request.beneficiary.name}</p>
                    <p className="text-xs text-muted-foreground">
                        {request.beneficiary.email}
                    </p>
                </div>
                <StatusBadge status={request.status} />
            </div>
            <dl className="grid gap-3 text-sm">
                <Detail label="Data type" value={request.dataType.label} />
                <Detail label="Reason" value={request.reason ?? '—'} />
                <Detail
                    label="Requested"
                    value={formatDate(request.requestedAt)}
                />
                <Detail
                    label="Review deadline"
                    value={formatDate(request.reviewExpiresAt)}
                />
                <Detail
                    label="Access expires"
                    value={
                        request.accessExpiresAt
                            ? formatDate(request.accessExpiresAt)
                            : '—'
                    }
                />
            </dl>
        </article>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}

function StatusBadge({ status }: { status: MedicalAccessRequestStatus }) {
    return (
        <Badge variant={statusVariants[status]} className="capitalize">
            {status}
        </Badge>
    );
}

function MedicalAccessPagination({
    pagination,
}: {
    pagination: PaginatedMedicalAccessRequests;
}) {
    if (pagination.meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            aria-label="Medical access request pagination"
        >
            <p className="text-sm text-muted-foreground">
                Showing {pagination.meta.from ?? 0}–{pagination.meta.to ?? 0} of{' '}
                {pagination.meta.total}
            </p>
            <div className="flex gap-2">
                <PaginationButton
                    label="Previous"
                    url={pagination.links.prev}
                />
                <PaginationButton label="Next" url={pagination.links.next} />
            </div>
        </nav>
    );
}

function PaginationButton({
    label,
    url,
}: {
    label: string;
    url: string | null;
}) {
    return url ? (
        <Link
            href={url}
            preserveScroll
            className={buttonVariants({ variant: 'outline', size: 'compact' })}
        >
            {label}
        </Link>
    ) : (
        <Button variant="outline" size="compact" disabled>
            {label}
        </Button>
    );
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
