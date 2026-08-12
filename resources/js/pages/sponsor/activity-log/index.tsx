import { Head, Link } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { WorkspaceActivityIcon } from '@/components/workspace-activity-icon';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import type { PaginatedWorkspaceActivities, WorkspaceActivity } from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export default function ActivityLogIndex({
    activities,
}: {
    activities: PaginatedWorkspaceActivities;
}) {
    return (
        <DashboardLayout>
            <Head title="Activity Log" />

            <div className="mx-auto w-full max-w-6xl py-2 sm:py-4">
                <PageHeader
                    title="Activity Log"
                    description="An immutable record of important workspace actions and outcomes."
                />

                <section className="pt-6" aria-label="Workspace activity">
                    <Card>
                        <CardContent className="p-0">
                            {activities.data.length === 0 ? (
                                <div className="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
                                    <div className="mb-4 flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <span
                                            className="text-xl"
                                            aria-hidden="true"
                                        >
                                            ···
                                        </span>
                                    </div>
                                    <h2 className="text-base font-semibold">
                                        No activity yet
                                    </h2>
                                    <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                        Important billing, beneficiary,
                                        employee, and medical-consent events
                                        will appear here.
                                    </p>
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {activities.data.map((activity) => (
                                        <ActivityRow
                                            key={activity.id}
                                            activity={activity}
                                        />
                                    ))}
                                </div>
                            )}

                            {activities.meta.last_page > 1 && (
                                <ActivityPagination activities={activities} />
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </DashboardLayout>
    );
}

function ActivityRow({ activity }: { activity: WorkspaceActivity }) {
    return (
        <article
            className={cn(
                'relative flex min-h-13 items-center gap-3 px-4 py-3 sm:px-5',
                // activity.isUnread && 'bg-information/[0.035]',
            )}
        >
            <WorkspaceActivityIcon
                icon={activity.icon}
                tone={activity.tone}
                className="size-7"
            />

            <div className="min-w-0 flex-1">
                <div className="flex items-start gap-3">
                    <h2 className="flex-1 text-[13px] leading-4 font-medium text-foreground">
                        {activity.title}
                    </h2>
                    {activity.isUnread && (
                        <span
                            className="mt-1.5 size-2 shrink-0 rounded-full bg-secondary"
                            aria-label="Unread activity"
                        />
                    )}
                </div>

                <p className="mt-0.5 text-[11px] leading-4 text-muted-foreground">
                    <span className="font-medium text-foreground/80">
                        {activity.actor.name}
                    </span>{' '}
                    &bull; {dateFormatter.format(new Date(activity.occurredAt))}
                </p>
            </div>
        </article>
    );
}

function ActivityPagination({
    activities,
}: {
    activities: PaginatedWorkspaceActivities;
}) {
    return (
        <nav
            className="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            aria-label="Activity log pagination"
        >
            <p className="text-sm text-muted-foreground">
                Showing {activities.meta.from ?? 0}–{activities.meta.to ?? 0} of{' '}
                {activities.meta.total}
            </p>
            <div className="flex gap-2">
                <PaginationButton
                    label="Previous"
                    url={activities.links.prev}
                />
                <PaginationButton label="Next" url={activities.links.next} />
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
