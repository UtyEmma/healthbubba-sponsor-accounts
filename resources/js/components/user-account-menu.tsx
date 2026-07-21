import { Link } from '@inertiajs/react';
import {
    ChevronsUpDownIcon,
    HeadphonesIcon,
    LogOutIcon,
    UserRoundIcon,
} from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { home } from '@/routes';
import accountSettings from '@/routes/account_settings';

export function UserAccountMenu() {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <button
                        type="button"
                        className="flex h-8 items-center gap-2 rounded-control px-1 outline-none hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring"
                        aria-label="Open account menu"
                    />
                }
            >
                <span className="flex size-7 items-center justify-center rounded-full bg-success-muted text-sm font-medium text-secondary-foreground">
                    IO
                </span>
                <span className="hidden text-sm font-medium sm:inline">
                    Ifeoma Okafor
                </span>
                <ChevronsUpDownIcon className="size-4 text-muted-foreground" />
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={8}
                className="w-[272px] overflow-hidden rounded-2xl p-0 shadow-card"
            >
                <DropdownMenuGroup>
                    <DropdownMenuLabel className="flex items-center gap-3 px-4 py-3 normal-case">
                        <img
                            src="/images/sponsor/beneficiary-alexander.png"
                            alt=""
                            className="size-9 shrink-0 rounded-full border border-border object-cover"
                        />
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-semibold text-foreground">
                                Ifeoma Okafor
                            </span>
                            <span className="block truncate pt-0.5 text-[13px] font-normal text-muted-foreground">
                                ifeomaokafor@example.com
                            </span>
                        </span>
                    </DropdownMenuLabel>
                </DropdownMenuGroup>

                <DropdownMenuSeparator className="m-0" />

                <DropdownMenuGroup className="p-2">
                    <DropdownMenuItem
                        render={<Link href={accountSettings.index()} />}
                        className="min-h-10 gap-3 rounded-lg px-2.5"
                    >
                        <UserRoundIcon className="text-muted-foreground" />
                        Account Settings
                    </DropdownMenuItem>
                    <DropdownMenuItem className="min-h-10 gap-3 rounded-lg px-2.5">
                        <HeadphonesIcon className="text-muted-foreground" />
                        Help/Support
                    </DropdownMenuItem>
                </DropdownMenuGroup>

                <DropdownMenuSeparator className="m-0" />

                <DropdownMenuGroup className="p-2">
                    <DropdownMenuItem
                        variant="destructive"
                        render={<Link href={home()} />}
                        className="min-h-10 gap-3 rounded-lg px-2.5"
                    >
                        <LogOutIcon />
                        Log out
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
