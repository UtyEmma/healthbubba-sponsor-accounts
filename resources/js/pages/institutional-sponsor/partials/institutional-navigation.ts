import {
    BarChart3Icon,
    BellIcon,
    LayoutGridIcon,
    MegaphoneIcon,
    StethoscopeIcon,
    TicketIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
    WalletIcon,
} from 'lucide-react';

import type { PortalNavigationItem } from '@/components/business-portal-shell';
import campaigns from '@/routes/campaigns';
import funding from '@/routes/funding';
import institutional from '@/routes/institutional';

export const institutionalNavigation: PortalNavigationItem[] = [
    {
        label: 'Dashboard',
        icon: LayoutGridIcon,
        href: institutional.dashboard().url,
    },

    {
        label: 'Funding',
        icon: WalletIcon,
        href: funding.index().url,
    },
    {
        label: 'Campaigns',
        icon: MegaphoneIcon,
        href: campaigns.index().url,
    },
    {
        label: 'Beneficiaries',
        icon: UsersRoundIcon,
        href: institutional.beneficiaries.index().url,
    },
    {
        label: 'Consultations',
        icon: StethoscopeIcon,
        href: institutional.consultations.index().url,
    },
    {
        label: 'Reports',
        icon: BarChart3Icon,
        href: institutional.reports.index().url,
    },
    {
        label: 'Enrollment Codes',
        icon: TicketIcon,
        href: institutional.enrollment_codes.index().url,
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
