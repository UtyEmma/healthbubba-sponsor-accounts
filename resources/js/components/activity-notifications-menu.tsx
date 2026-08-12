import { Form, Link, usePage } from '@inertiajs/react';
import { BellIcon } from 'lucide-react';

import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { WorkspaceActivityIcon } from '@/components/workspace-activity-icon';
import activityLog from '@/routes/activity_log';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function ActivityNotificationsMenu() {
    const { activityNotifications } = usePage().props;

    if (activityNotifications === null) {
        return null;
    }

    const { recent, unreadCount } = activityNotifications;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Workspace activity notifications"
                        className="relative"
                    />
                }
            >
                <BellIcon className="size-5" />
                {unreadCount > 0 && (
                    <span className="absolute -top-1 -right-1 flex min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-5 font-semibold text-destructive-foreground">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={8}
                className="w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl p-0 shadow-card"
            >
                <DropdownMenuGroup>
                    <DropdownMenuLabel className="flex items-center justify-between px-4 py-3 normal-case">
                        <span className="text-sm font-semibold text-foreground">
                            Recent activity
                        </span>
                        {unreadCount > 0 && (
                            <Form
                                {...activityLog.read_all.form()}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="link"
                                        size="compact"
                                        disabled={processing}
                                        className="h-auto px-0 text-xs"
                                    >
                                        Mark all as read
                                    </Button>
                                )}
                            </Form>
                        )}
                    </DropdownMenuLabel>
                </DropdownMenuGroup>

                <DropdownMenuSeparator className="m-0" />

                <DropdownMenuGroup className="p-2">
                    {recent.length === 0 ? (
                        <p className="px-3 py-8 text-center text-sm text-muted-foreground">
                            No workspace activity yet.
                        </p>
                    ) : (
                        recent.map((activity) => (
                            <DropdownMenuItem
                                key={activity.id}
                                render={<Link href={activityLog.index()} />}
                                className="items-start gap-3 rounded-xl px-2 py-2.5"
                            >
                                <WorkspaceActivityIcon
                                    icon={activity.icon}
                                    tone={activity.tone}
                                    className="size-8"
                                />
                                <span className="min-w-0 flex-1">
                                    <span className="flex items-start gap-2">
                                        <span className="line-clamp-2 flex-1 text-sm leading-5 font-medium text-foreground">
                                            {activity.title}
                                        </span>
                                        {activity.isUnread && (
                                            <span
                                                className="mt-1.5 size-2 shrink-0 rounded-full bg-information"
                                                aria-label="Unread"
                                            />
                                        )}
                                    </span>
                                    <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                        {activity.actor.name} ·{' '}
                                        {dateFormatter.format(
                                            new Date(activity.occurredAt),
                                        )}
                                    </span>
                                </span>
                            </DropdownMenuItem>
                        ))
                    )}
                </DropdownMenuGroup>

                <DropdownMenuSeparator className="m-0" />
                <div className="p-2">
                    <Link
                        href={activityLog.index()}
                        className={buttonVariants({
                            variant: 'ghost',
                            size: 'sm',
                            className: 'w-full',
                        })}
                    >
                        View all activity
                    </Link>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
