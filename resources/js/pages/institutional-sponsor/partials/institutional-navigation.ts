import {
    BarChart3Icon,
    BellIcon,
    CreditCardIcon,
    LayoutGridIcon,
    MegaphoneIcon,
    StethoscopeIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
} from 'lucide-react';

import type { PortalNavigationItem } from '@/components/business-portal-shell';
import institutional from '@/routes/institutional';
import plans from '@/routes/plans';

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
        href: institutional.campaigns.index().url,
    },
    {
        label: 'Consultations',
        icon: StethoscopeIcon,
        href: institutional.consultations().url,
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
        label: 'Plan & Billing',
        icon: CreditCardIcon,
        href: plans.index().url,
    },
    {
        label: 'Notifications',
        icon: BellIcon,
        href: institutional.notifications().url,
    },
];
