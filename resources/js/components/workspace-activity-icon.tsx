import {
    ArrowUpCircleIcon,
    CalendarClockIcon,
    CircleAlertIcon,
    CircleDotIcon,
    ClockIcon,
    CreditCardIcon,
    PauseCircleIcon,
    RotateCcwIcon,
    ShieldCheckIcon,
    ShieldXIcon,
    UserCheckIcon,
    UserPlusIcon,
    UserXIcon,
    WalletIcon,
} from 'lucide-react';

import { cn } from '@/lib/utils';
import type {
    WorkspaceActivityIcon as ActivityIconKey,
    WorkspaceActivityTone,
} from '@/types';

const icons = {
    wallet: WalletIcon,
    'credit-card': CreditCardIcon,
    'circle-alert': CircleAlertIcon,
    clock: ClockIcon,
    'arrow-up-circle': ArrowUpCircleIcon,
    'calendar-clock': CalendarClockIcon,
    'user-plus': UserPlusIcon,
    'user-check': UserCheckIcon,
    'user-x': UserXIcon,
    'pause-circle': PauseCircleIcon,
    'rotate-ccw': RotateCcwIcon,
    'shield-check': ShieldCheckIcon,
    'shield-x': ShieldXIcon,
    'circle-dot': CircleDotIcon,
} satisfies Record<ActivityIconKey, typeof CircleDotIcon>;

const toneClasses: Record<WorkspaceActivityTone, string> = {
    success: 'bg-success-muted text-success',
    warning: 'bg-warning-muted text-warning',
    destructive: 'bg-destructive-muted text-destructive',
    info: 'bg-information/10 text-information',
    neutral: 'bg-muted text-muted-foreground',
};

export function WorkspaceActivityIcon({
    icon,
    tone,
    className,
}: {
    icon: ActivityIconKey;
    tone: WorkspaceActivityTone;
    className?: string;
}) {
    const Icon = icons[icon] ?? CircleDotIcon;

    return (
        <span
            className={cn(
                'flex size-9 shrink-0 items-center justify-center rounded-full',
                toneClasses[tone],
                className,
            )}
        >
            <Icon className="size-4" aria-hidden="true" />
        </span>
    );
}
