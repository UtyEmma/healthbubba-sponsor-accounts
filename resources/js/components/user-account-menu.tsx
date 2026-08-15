import { Form, Link, usePage } from '@inertiajs/react';
import {
    ChevronsUpDownIcon,
    CheckIcon,
    HeadphonesIcon,
    LogOutIcon,
    UserRoundIcon,
} from 'lucide-react';
import { useState } from 'react';

import { LogoutConfirmationDialog } from '@/components/logout-confirmation-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import accountSettings from '@/routes/account_settings';
import workspaces from '@/routes/workspaces';
import Avatar from './avatar';

export function UserAccountMenu() {
    const { auth, workspaceOptions } = usePage().props;
    const [logoutOpen, setLogoutOpen] = useState(false);

    return (
        <>
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
                    <Avatar title={auth.user.name} />

                    <span className="hidden text-sm font-medium sm:inline">
                        {auth.user.name}
                    </span>
                    <ChevronsUpDownIcon className="size-4 text-muted-foreground" />
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    sideOffset={8}
                    className="w-[272px] overflow-hidden rounded-2xl p-0 shadow-card"
                >
                    <DropdownMenuGroup>
                        <DropdownMenuLabel className="flex items-center gap-2 px-4 py-3 normal-case">
                            <Avatar size="lg" title={auth.user.name} />

                            <span className="min-w-0">
                                <span className="block truncate text-sm font-semibold text-foreground">
                                    {auth.user.name}
                                </span>
                                <span className="block truncate text-[13px] font-normal text-muted-foreground">
                                    {auth.user.email}
                                </span>
                            </span>
                        </DropdownMenuLabel>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator className="m-0" />

                    {workspaceOptions.length > 1 && (
                        <>
                            <DropdownMenuGroup className="p-2">
                                <DropdownMenuLabel className="px-2.5 py-1 text-xs">
                                    Workspaces
                                </DropdownMenuLabel>
                                {workspaceOptions.map((workspace) => (
                                    <Form
                                        key={workspace.id}
                                        {...workspaces.select.form(
                                            workspace.id,
                                        )}
                                    >
                                        {({ processing }) => (
                                            <button
                                                type="submit"
                                                disabled={
                                                    processing ||
                                                    workspace.isCurrent
                                                }
                                                className="flex min-h-10 w-full items-center gap-3 rounded-lg px-2.5 text-left text-sm hover:bg-accent disabled:opacity-70"
                                            >
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate font-medium">
                                                        {workspace.name}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {workspaceTypeLabel(
                                                            workspace.type,
                                                        )}{' '}
                                                        · {workspace.roleLabel}
                                                    </span>
                                                </span>
                                                {workspace.isCurrent && (
                                                    <CheckIcon className="size-4 text-success" />
                                                )}
                                            </button>
                                        )}
                                    </Form>
                                ))}
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator className="m-0" />
                        </>
                    )}

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
                            render={<button type="button" className="w-full" />}
                            onClick={() => setLogoutOpen(true)}
                            className="min-h-10 cursor-pointer gap-3 rounded-lg px-2.5"
                        >
                            <LogOutIcon /> Log Out
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
            <LogoutConfirmationDialog
                open={logoutOpen}
                onOpenChange={setLogoutOpen}
            />
        </>
    );
}

function workspaceTypeLabel(type: string): string {
    return `${type.charAt(0).toUpperCase()}${type.slice(1)}`;
}
