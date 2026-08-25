import {
    BarChart3Icon,
    BellIcon,
    LayoutGridIcon,
    MegaphoneIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
} from 'lucide-react';

import type { PortalNavigationItem } from '@/components/business-portal-shell';
import campaigns from '@/routes/campaigns';
import institutional from '@/routes/institutional';

export const institutionalNavigation: PortalNavigationItem[] = [
    {
        label: 'Dashboard',
        icon: LayoutGridIcon,
        href: institutional.dashboard().url,
    },

    { label: 'Beneficiaries', icon: UsersRoundIcon },
    {
        label: 'Campaigns',
        icon: MegaphoneIcon,
        href: campaigns.index().url,
    },
    {
        label: 'Reports',
        icon: BarChart3Icon,
        href: institutional.reports().url,
    },
    {
        label: 'Team',
        icon: UserRoundCheckIcon,
        href: institutional.team().url,
    },
    {
        label: 'Notifications',
        icon: BellIcon,
        href: institutional.notifications().url,
    },
];
