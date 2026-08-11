import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3Icon,
    BellIcon,
    Building2Icon,
    HistoryIcon,
    LayoutGridIcon,
    MenuIcon,
    UsersRoundIcon,
    WalletCardsIcon,
} from 'lucide-react';
import type { ComponentType, ReactNode, SVGProps } from 'react';

import { BrandMark } from '@/components/brand-mark';
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
import business from '@/routes/business';

export type PortalNavigationItem = {
    label: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    href?: string;
};

const businessNavigation: PortalNavigationItem[] = [
    {
        label: 'Dashboard',
        icon: LayoutGridIcon,
        href: business.dashboard().url,
    },
    {
        label: 'Employees',
        icon: UsersRoundIcon,
        href: business.employees().url,
    },
    {
        label: 'Reports',
        icon: BarChart3Icon,
        href: business.reports().url,
    },
    { label: 'Wallet', icon: WalletCardsIcon },
    {
        label: 'Plan & Seats',
        icon: Building2Icon,
        href: business.plans().url,
    },
    { label: 'Activity Log', icon: HistoryIcon },
];

function PortalNavigation({
    navigation,
    label,
}: {
    navigation: PortalNavigationItem[];
    label: string;
}) {
    const currentPath = usePage().url.split('?')[0];

    return (
        <nav aria-label={label} className="p-3">
            <ul className="flex flex-col gap-1">
                {navigation.map((item) => {
                    const Icon = item.icon;
                    const isActive = item.href === currentPath;
                    const classes = isActive
                        ? 'flex min-h-8 items-center gap-3 rounded-md bg-accent px-2 text-sm font-medium text-secondary-foreground'
                        : 'flex min-h-10 items-center gap-3 rounded-md px-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-foreground';

                    return (
                        <li key={item.label}>
                            {item.href ? (
                                <Link
                                    href={item.href}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={classes}
                                >
                                    <Icon className="size-[18px]" />
                                    {item.label}
                                </Link>
                            ) : (
                                <span className={classes} aria-disabled="true">
                                    <Icon className="size-[18px]" />
                                    {item.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

function PortalSidebar({
    navigation,
    navigationLabel,
}: {
    navigation: PortalNavigationItem[];
    navigationLabel: string;
}) {
    return (
        <>
            <div className="flex h-16 items-center border-b border-border px-5">
                <BrandMark showName />
            </div>
            <PortalNavigation navigation={navigation} label={navigationLabel} />
        </>
    );
}

export function BusinessPortalShell({
    children,
    navigation = businessNavigation,
    navigationLabel = 'Business sponsor navigation',
}: {
    children: ReactNode;
    navigation?: PortalNavigationItem[];
    navigationLabel?: string;
}) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-border bg-sidebar lg:block">
                <PortalSidebar
                    navigation={navigation}
                    navigationLabel={navigationLabel}
                />
            </aside>
            
            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 flex h-12 items-center justify-between border-b border-border bg-background px-4 lg:justify-end">
                    <Sheet>
                        <SheetTrigger
                            render={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Open business navigation"
                                    className="lg:hidden"
                                />
                            }
                        >
                            <MenuIcon className="size-5" />
                        </SheetTrigger>
                        <SheetContent>
                            <SheetTitle className="sr-only">
                                Business sponsor navigation
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Navigate the HealthBubba business sponsor
                                portal.
                            </SheetDescription>
                            <PortalSidebar
                                navigation={navigation}
                                navigationLabel={navigationLabel}
                            />
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
                            <DropdownMenuContent align="end">
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
