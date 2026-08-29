import { Link } from '@inertiajs/react';

import { Button, buttonVariants } from '@/components/ui/button';
import type { Paginator } from '@/types';

export function RosterPagination<T>({
    pagination,
}: {
    pagination: Paginator<T>;
}) {
    if (pagination.meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            aria-label="Roster pagination"
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
