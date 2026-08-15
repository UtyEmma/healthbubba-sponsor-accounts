import { DownloadIcon } from 'lucide-react';

import { buttonVariants } from '@/components/ui/button';
import type { WorkforceStatusSummary } from '@/types';

export function ReportExport({
    workforce,
}: {
    workforce: WorkforceStatusSummary[];
}) {
    const csv = [
        'Status,Employees,Percentage',
        ...workforce.map(
            ({ label, count, percentage }) =>
                `${escapeCsvCell(label)},${count},${percentage}%`,
        ),
    ].join('\n');

    return (
        <a
            href={`data:text/csv;charset=utf-8,${encodeURIComponent(csv)}`}
            download="workforce-report.csv"
            className={`${buttonVariants({ variant: 'outline', size: 'compact' })} self-start sm:self-auto`}
        >
            <DownloadIcon className="size-4" />
            Export
        </a>
    );
}

function escapeCsvCell(value: string): string {
    return `"${value.replaceAll('"', '""')}"`;
}
