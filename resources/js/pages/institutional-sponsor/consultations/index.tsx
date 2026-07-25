import { Head } from '@inertiajs/react';
import { SearchIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { institutionalNavigation } from '../partials/institutional-navigation';

type ConsultationStatus = 'Completed' | 'Scheduled' | 'Cancelled';

const consultations = [
    {
        id: 1,
        beneficiary: 'David Smith',
        date: '10/25/2025',
        type: 'GP',
        status: 'Completed' as ConsultationStatus,
        payment: 'Sponsor Coverage',
    },
    {
        id: 2,
        beneficiary: 'Alexander Ogunyemi',
        date: '10/25/2025',
        type: 'Specialist',
        status: 'Completed' as ConsultationStatus,
        payment: 'Personal',
    },
    {
        id: 3,
        beneficiary: 'Dominic Barrow',
        date: '10/25/2025',
        type: 'Specialist',
        status: 'Scheduled' as ConsultationStatus,
        payment: 'Sponsor Coverage',
    },
    {
        id: 4,
        beneficiary: 'David Smith',
        date: '10/25/2025',
        type: 'GP',
        status: 'Cancelled' as ConsultationStatus,
        payment: 'Personal',
    },
    {
        id: 5,
        beneficiary: 'David Smith',
        date: '10/25/2025',
        type: 'Specialist',
        status: 'Cancelled' as ConsultationStatus,
        payment: 'Sponsor Coverage',
    },
];

export default function InstitutionalConsultationsPage() {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');

    const filteredConsultations = useMemo(
        () =>
            consultations.filter((consultation) => {
                const matchesQuery = consultation.beneficiary
                    .toLowerCase()
                    .includes(query.toLowerCase());
                const matchesStatus =
                    status === 'all' || consultation.status === status;

                return matchesQuery && matchesStatus;
            }),
        [query, status],
    );

    return (
        <>
            <Head title="Consultations" />
            <BusinessPortalShell
                navigation={institutionalNavigation}
                navigationLabel="Institutional sponsor navigation"
            >
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Consultations"
                        description="Sponsored and personally-funded consultations across your program."
                    />

                    <Card className="mt-4 overflow-hidden">
                        <CardHeader className="flex-col justify-between gap-4 border-b px-6 py-4 sm:flex-row sm:items-center">
                            <CardTitle className="text-base">
                                All consultations (
                                {filteredConsultations.length})
                            </CardTitle>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <label className="relative">
                                    <span className="sr-only">
                                        Search consultations
                                    </span>
                                    <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        placeholder="Search by name, email, community"
                                        className="w-full pl-9 sm:w-[270px]"
                                    />
                                </label>
                                <Select
                                    value={status}
                                    onValueChange={(value) =>
                                        setStatus(value ?? 'all')
                                    }
                                >
                                    <SelectTrigger
                                        className="w-full sm:w-[87px]"
                                        aria-label="Filter by status"
                                    >
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Status
                                        </SelectItem>
                                        <SelectItem value="Completed">
                                            Completed
                                        </SelectItem>
                                        <SelectItem value="Scheduled">
                                            Scheduled
                                        </SelectItem>
                                        <SelectItem value="Cancelled">
                                            Cancelled
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>
                        <CardContent className="overflow-x-auto p-0">
                            <Table className="min-w-[800px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-8">
                                            Beneficiary
                                        </TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="pr-8">
                                            Payment source
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredConsultations.map(
                                        (consultation) => (
                                            <TableRow
                                                key={consultation.id}
                                                className={
                                                    consultation.status ===
                                                    'Scheduled'
                                                        ? 'bg-success-muted/40'
                                                        : undefined
                                                }
                                            >
                                                <TableCell className="h-[49px] pl-8 font-medium">
                                                    {consultation.beneficiary}
                                                </TableCell>
                                                <TableCell className="h-[49px] text-muted-foreground">
                                                    {consultation.date}
                                                </TableCell>
                                                <TableCell className="h-[49px]">
                                                    <ConsultationTypeBadge
                                                        type={consultation.type}
                                                    />
                                                </TableCell>
                                                <TableCell className="h-[49px]">
                                                    <ConsultationStatusBadge
                                                        status={
                                                            consultation.status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="h-[49px] pr-8">
                                                    <Badge
                                                        variant={
                                                            consultation.payment ===
                                                            'Sponsor Coverage'
                                                                ? 'success'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {consultation.payment}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ),
                                    )}
                                </TableBody>
                            </Table>
                            {filteredConsultations.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    No consultations match your filters.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </BusinessPortalShell>
        </>
    );
}

function ConsultationTypeBadge({ type }: { type: string }) {
    return (
        <Badge
            variant="secondary"
            className={
                type === 'Specialist'
                    ? 'border-blue-200 bg-blue-100 text-blue-700'
                    : undefined
            }
        >
            {type}
        </Badge>
    );
}

function ConsultationStatusBadge({ status }: { status: ConsultationStatus }) {
    if (status === 'Completed') {
        return <Badge variant="success">{status}</Badge>;
    }

    if (status === 'Cancelled') {
        return <Badge variant="destructive">{status}</Badge>;
    }

    return (
        <Badge className="border-blue-200 bg-blue-100 text-blue-700">
            {status}
        </Badge>
    );
}
