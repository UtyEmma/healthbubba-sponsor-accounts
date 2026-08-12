import { MenuIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { ActivityNotificationsMenu } from '@/components/activity-notifications-menu';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserAccountMenu } from '@/components/user-account-menu';
import { Sidebar } from './partials/sidebar';

export function DashboardLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-border bg-sidebar lg:block">
                <Sidebar />
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

                            <Sidebar />
                        </SheetContent>
                    </Sheet>

                    <div className="flex h-full items-center gap-2">
                        <ActivityNotificationsMenu />
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
