import { Head } from '@inertiajs/react';
import {
    ArrowLeftRightIcon,
    CreditCardIcon,
    ShieldCheckIcon,
    UserRoundPlusIcon,
} from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Activity = {
    title: string;
    metadata: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    tone: 'blue' | 'green' | 'highlight';
};

const activities: Activity[] = [
    {
        title: 'Scheduled downgrade to Basic Plan at next renewal',
        metadata: 'Ifeoma Okafor · 24 Jun 2026, 21:01',
        icon: CreditCardIcon,
        tone: 'blue',
    },
    {
        title: 'Invited beneficiary Emeka Okafor',
        metadata: 'Ifeoma Okafor · 19 Jun 2026, 09:00',
        icon: UserRoundPlusIcon,
        tone: 'green',
    },
    {
        title: 'Requested diagnosis access for Ngozi Okafor',
        metadata: 'Ifeoma Okafor · 18 Jun 2026, 09:00',
        icon: ShieldCheckIcon,
        tone: 'highlight',
    },
    {
        title: 'Transferred ₦5,000 to Adaeze Okafor',
        metadata: 'Ifeoma Okafor · 3 Jun 2026, 09:00',
        icon: ArrowLeftRightIcon,
        tone: 'blue',
    },
];

export default function ActivityLogIndex() {
    return (
        <>
            <Head title="Activity Log" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Activity Log"
                        description="An immutable record of account lifecycle changes."
                    />

                    <section className="pt-6" aria-label="Account activity">
                        <Card>
                            <CardContent className="divide-y p-0">
                                {activities.map((activity) => (
                                    <ActivityRow
                                        key={activity.title}
                                        activity={activity}
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </PortalShell>
        </>
    );
}

function ActivityRow({ activity }: { activity: Activity }) {
    const Icon = activity.icon;

    return (
        <article className="flex min-h-[68px] items-center gap-4 px-4 py-3 sm:px-5">
            <span
                className={cn(
                    'flex size-8 shrink-0 items-center justify-center rounded-full',
                    activity.tone === 'highlight' && 'bg-success-muted',
                )}
            >
                <Icon
                    className={cn(
                        'size-4',
                        activity.tone === 'blue'
                            ? 'text-blue-600'
                            : 'text-success',
                    )}
                />
            </span>
            <div className="min-w-0">
                <h2 className="text-sm leading-5 font-medium">
                    {activity.title}
                </h2>
                <p className="text-xs leading-4 text-muted-foreground">
                    {activity.metadata}
                </p>
            </div>
        </article>
    );
}
