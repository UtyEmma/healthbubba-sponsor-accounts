import {
    BarChart3Icon,
    BellIcon,
    LayoutGridIcon,
    ShieldPlusIcon,
    StethoscopeIcon,
    TicketIcon,
    UserRoundCheckIcon,
    UsersRoundIcon,
} from 'lucide-react';

import type { PortalNavigationItem } from '@/components/business-portal-shell';
import institutional from '@/routes/institutional';

export const institutionalNavigation: PortalNavigationItem[] = [
    {
        label: 'Dashboard',
        icon: LayoutGridIcon,
        href: institutional.dashboard().url,
    },
    {
        label: 'Coverage',
        icon: ShieldPlusIcon,
        href: institutional.coverage().url,
    },
    { label: 'Beneficiaries', icon: UsersRoundIcon },
    {
        label: 'Enrollment Codes',
        icon: TicketIcon,
        href: institutional.enrollment_codes().url,
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
        label: 'Notifications',
        icon: BellIcon,
        href: institutional.notifications().url,
    },
];
