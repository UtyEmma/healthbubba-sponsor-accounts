import { Link, usePage } from '@inertiajs/react';
import { BellIcon, MenuIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { BrandMark } from '@/components/brand-mark';
import type { SidebarIcon } from '@/components/sidebar-icons';
import {
    ActivityLogSidebarIcon,
    BeneficiariesSidebarIcon,
    ConsultationsSidebarIcon,
    DashboardSidebarIcon,
    MedicalAccessSidebarIcon,
    PlanBillingSidebarIcon,
    WalletSidebarIcon,
} from '@/components/sidebar-icons';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserAccountMenu } from '@/components/user-account-menu';
import { home } from '@/routes';
import activityLog from '@/routes/activity_log';
import beneficiaries from '@/routes/beneficiaries';
import consultations from '@/routes/consultations';
import medicalAccess from '@/routes/medical_access';
import plans from '@/routes/plans';
import wallet from '@/routes/wallet';

type NavigationItem = {
    label: string;
    icon: SidebarIcon;
    href?: ReturnType<typeof home>;
};

const navigation: NavigationItem[] = [
    { label: 'Dashboard', icon: DashboardSidebarIcon, href: home() },
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
    {
        label: 'Medical Access',
        icon: MedicalAccessSidebarIcon,
        href: medicalAccess.index(),
    },
    { label: 'Wallet', icon: WalletSidebarIcon, href: wallet.index() },
    {
        label: 'Plan & Billing',
        icon: PlanBillingSidebarIcon,
        href: plans.index(),
    },
    {
        label: 'Activity Log',
        icon: ActivityLogSidebarIcon,
        href: activityLog.index(),
    },
];

function PortalNavigation() {
    const currentPath = usePage().url.split('?')[0];

    return (
        <nav aria-label="Primary" className="p-3">
            <ul className="flex flex-col gap-1">
                {navigation.map((item) => {
                    const Icon = item.icon;
                    const isActive = item.href?.url === currentPath;

                    return (
                        <li key={item.label}>
                            {item.href ? (
                                <Link
                                    href={item.href}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={
                                        isActive
                                            ? 'flex min-h-8 items-center gap-3 rounded-md bg-accent px-2 text-sm font-medium text-secondary-foreground'
                                            : 'flex min-h-10 items-center gap-3 rounded-md px-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-foreground'
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

function ShellSidebar() {
    return (
        <>
            <div className="flex h-16 items-center border-b border-border px-5">
                <BrandMark showName />
            </div>
            <PortalNavigation />
        </>
    );
}

export function PortalShell({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-border bg-sidebar lg:block">
                <ShellSidebar />
            </aside>

            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-border bg-background px-4 lg:justify-end">
                    <Sheet>
                        <SheetTrigger
                            render={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Open navigation"
                                    className="lg:hidden"
                                />
                            }
                        >
                            <MenuIcon className="size-5" />
                        </SheetTrigger>
                        <SheetContent>
                            <SheetTitle className="sr-only">
                                Sponsor navigation
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Navigate the HealthBubba sponsor portal.
                            </SheetDescription>
                            <ShellSidebar />
                        </SheetContent>
                    </Sheet>

                    <div className="flex h-full items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                render={
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Notifications"
                                    />
                                }
                            >
                                <BellIcon className="size-5" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-xs" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuLabel>
                                        Notifications
                                    </DropdownMenuLabel>
                                </DropdownMenuGroup>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem disabled>
                                    No new notifications
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Separator
                            orientation="vertical"
                            className="h-4 bg-divider"
                        />
                        <UserAccountMenu />
                    </div>
                </header>
                <main className="min-h-[calc(100vh-48px)] bg-background p-4">
                    {children}
                </main>
            </div>
        </div>
    );
}
