import { Link, usePage } from '@inertiajs/react';
import { BarChart3Icon, BellIcon, UsersRoundIcon } from 'lucide-react';
import { useMemo } from 'react';

import { BrandMark } from '@/components/brand-mark';
import type { SidebarIcon } from '@/components/sidebar-icons';
import {
    ActivityLogSidebarIcon,
    BeneficiariesSidebarIcon,
    CampaignSidebarIcon,
    ConsultationsSidebarIcon,
    DashboardSidebarIcon,
    MedicalAccessSidebarIcon,
    PlanBillingSidebarIcon,
    TeamSidebarIcon,
    WalletSidebarIcon,
} from '@/components/sidebar-icons';
import { home } from '@/routes';
import activity_log from '@/routes/activity_log';
import beneficiaries from '@/routes/beneficiaries';
import business from '@/routes/business';
import consultations from '@/routes/consultations';
import institutional, { notifications } from '@/routes/institutional';
import medical_access from '@/routes/medical_access';
import plans from '@/routes/plans';
import team from '@/routes/team';
import wallet from '@/routes/wallet';
import campaigns from '@/routes/campaigns';
import { cn } from '@/lib/utils';

type NavigationItem = {
    label: string;
    icon: SidebarIcon;
    href?: ReturnType<typeof home>;
};

function Navigation() {
    const currentPath = usePage().url.split('?')[0];

    const { workspace, workspacePermissions } = usePage().props;

    const navigation = useMemo(() => {
        const navigation: NavigationItem[] = [
            { label: 'Dashboard', icon: DashboardSidebarIcon, href: home() },
        ];

        if (workspace.type == 'institution') {
            navigation.push({
                label: 'Campaigns',
                icon: CampaignSidebarIcon,
                href: campaigns.index(),
            });
        }

        if (workspace.type == 'individual') {
            navigation.push(
                {
                    label: 'Beneficiaries',
                    icon: BeneficiariesSidebarIcon,
                    href: beneficiaries.index(),
                },
                {
                    label: 'Consultations',
                    icon: ConsultationsSidebarIcon,
                    href: consultations.index(),
                },
            );
        }

        if (workspace.type == 'individual') {
            navigation.push({
                label: 'Medical Access',
                icon: MedicalAccessSidebarIcon,
                href: medical_access.index(),
            });
        }

        if (workspace.type == 'business') {
            navigation.push(
                {
                    label: 'Employees',
                    icon: UsersRoundIcon,
                    href: business.employees(),
                },
                {
                    label: 'Reports',
                    icon: BarChart3Icon,
                    href: business.reports(),
                },
            );
        }

        if (workspace.type == 'institution') {
            navigation.push({
                label: 'Reports',
                icon: BarChart3Icon,
                href: institutional.reports(),
            });
        }

        navigation.push({
            label: 'Wallet',
            icon: WalletSidebarIcon,
            href: wallet.index(),
        });

        if (
            (workspace.type == 'individual' || workspace.type == 'business') &&
            workspacePermissions.canViewFinancial
        ) {
            navigation.push({
                label: 'Plan & Billing',
                icon: PlanBillingSidebarIcon,
                href: plans.index(),
            });
        }

        navigation.push({
            label: 'Team',
            icon: TeamSidebarIcon,
            href: team.index(),
        });

        if (workspace.type == 'institution') {
            navigation.push({
                label: 'Notifications',
                icon: BellIcon,
                href: notifications(),
            });
        }

        if (['individual', 'business'].includes(workspace.type)) {
            navigation.push({
                label: 'Activity Log',
                icon: ActivityLogSidebarIcon,
                href: activity_log.index(),
            });
        }

        return navigation;
    }, [workspace, workspacePermissions.canViewFinancial]);

    return (
        <nav aria-label="Primary" className="p-3">
            <ul className="flex flex-col gap-1">
                {navigation.map((item) => {
                    const Icon = item.icon;
                    const itemPath = item.href?.url;
                    const isActive =
                        itemPath === currentPath ||
                        (itemPath !== undefined &&
                            itemPath !== '/' &&
                            currentPath.startsWith(`${itemPath}/`));

                    return (
                        <li key={item.label}>
                            {item.href ? (
                                <Link
                                    href={item.href}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={
                                        cn('min-h-10',
                                        isActive
                                            ? 'flex  items-center gap-3 rounded-md bg-accent px-2 text-sm font-medium text-secondary'
                                            : 'flex items-center gap-3 rounded-md px-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-secondary')
                                    }
                                >
                                    <Icon className="size-[18px] shrink-0" />
                                    {item.label}
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    aria-label={`${item.label} (coming soon)`}
                                    className="flex min-h-10 w-full items-center gap-3 rounded-md px-2 text-sm font-medium text-muted-foreground opacity-80"
                                >
                                    <Icon className="size-[18px] shrink-0" />
                                    {item.label}
                                </button>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

export function Sidebar() {
    return (
        <>
            <div className="flex h-16 items-center border-b border-border px-5">
                <BrandMark showName />
            </div>
            <Navigation />
        </>
    );
}
