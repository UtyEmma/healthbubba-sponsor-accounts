import { Head } from '@inertiajs/react';
import {
    CalendarClockIcon,
    CheckCheckIcon,
    HeartPulseIcon,
    UsersRoundIcon,
} from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';

const initialNotifications = [
    {
        id: 1,
        title: 'Scheduled consultation coverage utilization has reached 50% of the annual pool.',
        date: '20 Jun 2026',
        tone: 'amber',
        icon: HeartPulseIcon,
        unread: true,
    },
    {
        id: 2,
        title: 'Enrollment code HOPE-OGUI-2026 expires in 40 days.',
        date: '19 Jun 2026',
        tone: 'red',
        icon: CalendarClockIcon,
        unread: true,
    },
    {
        id: 3,
        title: '12 new beneficiaries registered via HOPE-SABON-2026 this week.',
        date: '18 Jun 2026',
        tone: 'blue',
        icon: UsersRoundIcon,
        unread: false,
    },
];

export default function InstitutionalNotificationsPage() {
    const [readIds, setReadIds] = useState<number[]>([]);
    const unreadCount = initialNotifications.filter(
        (notification) =>
            notification.unread && !readIds.includes(notification.id),
    ).length;

    function markAllRead() {
        setReadIds(
            initialNotifications
                .filter((notification) => notification.unread)
                .map((notification) => notification.id),
        );
    }

    return (
        <>
            <Head title="Notifications" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Notifications"
                        description={`${unreadCount} unread ${unreadCount === 1 ? 'alert' : 'alerts'}.`}
                        action={
                            <Button
                                variant="outline"
                                onClick={markAllRead}
                                disabled={unreadCount === 0}
                            >
                                <CheckCheckIcon className="size-4" />
                                Mark all read
                            </Button>
                        }
                    />

                    <Card className="mt-6 overflow-hidden">
                        <CardContent className="divide-y p-0">
                            {initialNotifications.map((notification) => {
                                const isUnread =
                                    notification.unread &&
                                    !readIds.includes(notification.id);
                                const Icon = notification.icon;

                                return (
                                    <article
                                        key={notification.id}
                                        className="flex min-h-[73px] items-center gap-4 px-4 py-3"
                                    >
                                        <span
                                            className={cn(
                                                'flex size-9 shrink-0 items-center justify-center rounded-full',
                                                notification.tone === 'amber' &&
                                                    'bg-warning-muted text-warning',
                                                notification.tone === 'red' &&
                                                    'text-destructive',
                                                notification.tone === 'blue' &&
                                                    'text-blue-600',
                                            )}
                                        >
                                            <Icon className="size-4" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <h2 className="text-sm font-normal">
                                                {notification.title}
                                            </h2>
                                            <p className="pt-0.5 text-xs text-muted-foreground">
                                                {notification.date}
                                            </p>
                                        </div>
                                        {isUnread && (
                                            <Button
                                                variant="ghost"
                                                size="compact"
                                                onClick={() =>
                                                    setReadIds((ids) => [
                                                        ...ids,
                                                        notification.id,
                                                    ])
                                                }
                                            >
                                                Mark read
                                            </Button>
                                        )}
                                    </article>
                                );
                            })}
                        </CardContent>
                    </Card>
                </div>
            </DashboardLayout>
        </>
    );
}
