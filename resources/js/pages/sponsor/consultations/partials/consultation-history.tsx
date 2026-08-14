import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { useState } from 'react';

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
import { cn } from '@/lib/utils';

const consultations = [
    ['GP Consultation', '1 GP from shared pool'],
    ['GP Consultation', '1 GP from shared pool'],
    ['Specialist', '1 Specialist from shared pool'],
    ['GP Consultation', '1 GP from shared pool'],
    ['GP Consultation', '1 GP from shared pool'],
] as const;

export function ConsultationHistory() {
    const [page, setPage] = useState(1);

    return (
        <section className="pt-5" aria-labelledby="consultation-history-title">
            <Card className="overflow-hidden">
                <div className="flex h-14 items-center px-6">
                    <h2
                        id="consultation-history-title"
                        className="text-base font-semibold"
                    >
                        Consultation history
                    </h2>
                </div>
                <div className="overflow-x-auto">
                    <Table className="min-w-[1000px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[184px] pl-8">
                                    Beneficiary
                                </TableHead>
                                <TableHead className="w-[183px]">
                                    Consultation Type
                                </TableHead>
                                <TableHead className="w-[183px]">
                                    Status
                                </TableHead>
                                <TableHead className="w-[183px]">
                                    Joined
                                </TableHead>
                                <TableHead className="w-[183px]">
                                    Consultation History
                                </TableHead>
                                <TableHead>Cost</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {consultations.map(([type, cost], index) => (
                                <TableRow key={`${type}-${index}`}>
                                    <TableCell className="h-[49px] pl-8 font-medium">
                                        David Smith
                                    </TableCell>
                                    <TableCell className="h-[49px] text-muted-foreground">
                                        {type}
                                    </TableCell>
                                    <TableCell className="h-[49px] text-muted-foreground">
                                        Dr. Sarah Johnson
                                    </TableCell>
                                    <TableCell className="h-[49px] whitespace-nowrap text-muted-foreground">
                                        24 Oct 2025, 10:30 AM
                                    </TableCell>
                                    <TableCell className="h-[49px]">
                                        <Badge variant="success">
                                            Completed
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="h-[49px] text-muted-foreground">
                                        {cost}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <footer className="flex min-h-[49px] flex-col items-center justify-between gap-3 border-t border-border px-4 py-2 sm:flex-row">
                    <p className="text-xs text-muted-foreground">
                        Showing 10 of 100
                    </p>
                    <nav
                        aria-label="Consultation history pagination"
                        className="flex h-10 items-stretch overflow-hidden rounded-xl border border-border"
                    >
                        <PageButton
                            label="Previous"
                            disabled={page === 1}
                            onClick={() =>
                                setPage((current) => Math.max(1, current - 1))
                            }
                        >
                            <ChevronLeftIcon className="size-4" />
                            Previous
                        </PageButton>
                        {[1, 2, 3].map((number) => (
                            <PageButton
                                key={number}
                                label={`Page ${number}`}
                                active={page === number}
                                onClick={() => setPage(number)}
                            >
                                {number}
                            </PageButton>
                        ))}
                        <span className="flex min-w-10 items-center justify-center border-l border-border text-xs text-muted-foreground">
                            …
                        </span>
                        <PageButton
                            label="Page 20"
                            active={page === 20}
                            onClick={() => setPage(20)}
                        >
                            20
                        </PageButton>
                        <PageButton
                            label="Next"
                            onClick={() =>
                                setPage((current) => Math.min(20, current + 1))
                            }
                        >
                            Next
                            <ChevronRightIcon className="size-4" />
                        </PageButton>
                    </nav>
                </footer>
            </Card>
        </section>
    );
}

function PageButton({
    label,
    active = false,
    disabled = false,
    onClick,
    children,
}: {
    label: string;
    active?: boolean;
    disabled?: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            aria-current={active ? 'page' : undefined}
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'flex min-w-10 items-center justify-center gap-1 border-l border-border px-2.5 text-xs transition-colors first:border-l-0 hover:bg-accent disabled:text-subtle',
                active && 'bg-muted font-medium text-foreground',
            )}
        >
            {children}
        </button>
    );
}
