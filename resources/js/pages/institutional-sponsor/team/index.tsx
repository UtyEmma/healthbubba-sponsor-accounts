import { Form, Head, Link } from '@inertiajs/react';
import { UserRoundPlusIcon } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { DashboardLayout } from '@/layouts/dashboard';
import team from '@/routes/team';
import type {
    PaginatedWorkspaceTeamMembers,
    WorkspaceMemberStatus,
    WorkspaceTeamMember,
} from '@/types';

interface TeamPageProps {
    members: PaginatedWorkspaceTeamMembers;
    canManage: boolean;
}

const statusVariants: Record<
    WorkspaceMemberStatus,
    'success' | 'warning' | 'secondary' | 'destructive'
> = {
    active: 'success',
    invited: 'warning',
    disabled: 'secondary',
    declined: 'destructive',
    cancelled: 'destructive',
    expired: 'secondary',
};

export default function WorkspaceTeamPage({
    members,
    canManage,
}: TeamPageProps) {
    const [inviteOpen, setInviteOpen] = useState(false);
    const [confirmation, setConfirmation] = useState<{
        member: WorkspaceTeamMember;
        action: 'disable' | 'cancel' | 'resend';
    } | null>(null);

    return (
        <DashboardLayout>
            <Head title="Team Management" />
            <div className="mx-auto w-full max-w-6xl">
                <PageHeader
                    title="Team Management"
                    description="Invite colleagues and control what they can do."
                    action={
                        canManage ? (
                            <Button
                                size="compact"
                                onClick={() => setInviteOpen(true)}
                            >
                                <UserRoundPlusIcon className="size-4" />
                                Invite Member
                            </Button>
                        ) : undefined
                    }
                />

                <Card className="mt-4 overflow-hidden">
                    <CardHeader className="h-14 justify-center border-b px-6 py-0">
                        <CardTitle className="text-base">
                            Members ({members.meta.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto p-0">
                        <Table className="min-w-[760px]">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-8">
                                        Member
                                    </TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    {canManage && (
                                        <TableHead className="pr-8 text-right">
                                            Action
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {members.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={canManage ? 4 : 3}
                                            className="h-32 text-center text-muted-foreground"
                                        >
                                            No team members have been added yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    members.data.map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="h-[69px] pl-8">
                                                <span className="block font-medium">
                                                    {member.name}
                                                    {member.isCurrentUser
                                                        ? ' (You)'
                                                        : ''}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {member.email}
                                                </span>
                                            </TableCell>
                                            <TableCell className="h-[69px] text-muted-foreground">
                                                {canManage &&
                                                member.role !== 'owner' &&
                                                (member.status === 'active' ||
                                                    member.status ===
                                                        'disabled') &&
                                                !member.isCurrentUser ? (
                                                    <Form
                                                        {...team.members.role.update.form(
                                                            member.id,
                                                        )}
                                                    >
                                                        {({ processing }) => (
                                                            <Select
                                                                name="role"
                                                                defaultValue={
                                                                    member.role
                                                                }
                                                                disabled={
                                                                    processing
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    event.currentTarget.form?.requestSubmit()
                                                                }
                                                                className="h-9"
                                                                containerClassName="w-auto"
                                                                aria-label={`Role for ${member.name}`}
                                                            >
                                                                <option value="administrator">
                                                                    Administrator
                                                                </option>
                                                                <option value="viewer">
                                                                    Viewer
                                                                </option>
                                                            </Select>
                                                        )}
                                                    </Form>
                                                ) : (
                                                    member.roleLabel
                                                )}
                                            </TableCell>
                                            <TableCell className="h-[69px]">
                                                <Badge
                                                    variant={
                                                        statusVariants[
                                                            member.status
                                                        ]
                                                    }
                                                >
                                                    {member.statusLabel}
                                                </Badge>
                                            </TableCell>
                                            {canManage && (
                                                <TableCell className="h-[69px] pr-8 text-right">
                                                    {!member.isCurrentUser &&
                                                        member.role !==
                                                            'owner' && (
                                                            <MemberActions
                                                                member={member}
                                                                onConfirm={(
                                                                    action,
                                                                ) =>
                                                                    setConfirmation(
                                                                        {
                                                                            member,
                                                                            action,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        )}
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    <Pagination members={members} />
                </Card>
            </div>

            <InviteDialog open={inviteOpen} onOpenChange={setInviteOpen} />
            <ConfirmationDialog
                confirmation={confirmation}
                onOpenChange={(open) => !open && setConfirmation(null)}
            />
        </DashboardLayout>
    );
}

function MemberActions({
    member,
    onConfirm,
}: {
    member: WorkspaceTeamMember;
    onConfirm: (action: 'disable' | 'cancel' | 'resend') => void;
}) {
    if (member.status === 'active') {
        return (
            <Button
                variant="outline"
                size="compact"
                className="text-destructive"
                onClick={() => onConfirm('disable')}
            >
                Disable
            </Button>
        );
    }

    if (member.status === 'disabled') {
        return (
            <Form {...team.members.access.update.form(member.id)}>
                {({ processing }) => (
                    <>
                        <input type="hidden" name="enabled" value="1" />
                        <Button
                            type="submit"
                            variant="outline"
                            size="compact"
                            disabled={processing}
                        >
                            Enable
                        </Button>
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="flex justify-end gap-2">
            <Button
                type="button"
                variant="outline"
                size="compact"
                onClick={() => onConfirm('resend')}
            >
                Resend
            </Button>
            {member.status === 'invited' && (
                <Button
                    variant="outline"
                    size="compact"
                    className="text-destructive"
                    onClick={() => onConfirm('cancel')}
                >
                    Cancel
                </Button>
            )}
        </div>
    );
}

function InviteDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="border-b px-6 py-5">
                    <DialogTitle className="text-base">
                        Invite a team member
                    </DialogTitle>
                    <DialogDescription>
                        They'll receive an email invitation to join your sponsor
                        account.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...team.invitations.store.form()}
                    resetOnSuccess
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 px-6 py-4">
                                <label className="grid gap-2 text-[13px] font-medium">
                                    Full name
                                    <Input name="name" required />
                                    <InputError error={errors.name} />
                                </label>
                                <label className="grid gap-2 text-[13px] font-medium">
                                    Email address
                                    <Input name="email" type="email" required />
                                    <InputError error={errors.email} />
                                </label>
                                <label className="grid gap-2 text-[13px] font-medium">
                                    Role
                                    <Select
                                        name="role"
                                        required
                                        defaultValue=""
                                    >
                                        <option value="" disabled>
                                            Select
                                        </option>
                                        <option value="administrator">
                                            Administrator
                                        </option>
                                        <option value="viewer">Viewer</option>
                                    </Select>
                                    <InputError error={errors.role} />
                                </label>
                            </div>
                            <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                                <DialogClose
                                    render={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="compact"
                                        />
                                    }
                                >
                                    Cancel
                                </DialogClose>
                                <Button
                                    type="submit"
                                    size="compact"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Sending...'
                                        : 'Send Invitation'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function ConfirmationDialog({
    confirmation,
    onOpenChange,
}: {
    confirmation: {
        member: WorkspaceTeamMember;
        action: 'disable' | 'cancel' | 'resend';
    } | null;
    onOpenChange: (open: boolean) => void;
}) {
    if (!confirmation) {
        return null;
    }

    const isDisable = confirmation.action === 'disable';
    const isResend = confirmation.action === 'resend';
    const form = isDisable
        ? team.members.access.update.form(confirmation.member.id)
        : isResend
          ? team.invitations.resend.form(confirmation.member.id)
          : team.invitations.cancel.form(confirmation.member.id);

    return (
        <Dialog open onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isDisable
                            ? 'Disable Member'
                            : isResend
                              ? 'Resend Invitation'
                              : 'Cancel Invitation'}
                    </DialogTitle>
                    <DialogDescription>
                        {isDisable
                            ? `Are you sure you wish to disable ${confirmation.member.name}'s workspace access?`
                            : isResend
                              ? `Are you sure you wish to resend the invitation to ${confirmation.member.name}? Their previous invitation link will no longer work.`
                              : `Are you sure you wish to cancel the invitation for ${confirmation.member.name}?`}
                    </DialogDescription>
                </DialogHeader>
                <Form {...form} onSuccess={() => onOpenChange(false)}>
                    {({ processing }) => (
                        <DialogFooter>
                            {!isDisable ? null : (
                                <input type="hidden" name="enabled" value="0" />
                            )}
                            <DialogClose
                                render={
                                    <Button type="button" variant="outline" />
                                }
                            >
                                Cancel
                            </DialogClose>
                            <Button
                                type="submit"
                                variant={isResend ? 'primary' : 'destructive'}
                                disabled={processing}
                            >
                                {processing
                                    ? 'Updating...'
                                    : isDisable
                                      ? 'Disable'
                                      : isResend
                                        ? 'Resend Invitation'
                                        : 'Cancel Invitation'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function Pagination({ members }: { members: PaginatedWorkspaceTeamMembers }) {
    if (members.meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex items-center justify-between border-t px-6 py-4"
            aria-label="Team pagination"
        >
            <p className="text-sm text-muted-foreground">
                Showing {members.meta.from ?? 0}–{members.meta.to ?? 0} of{' '}
                {members.meta.total}
            </p>
            <div className="flex gap-2">
                {members.links.prev ? (
                    <Link
                        href={members.links.prev}
                        preserveScroll
                        className={buttonVariants({
                            variant: 'outline',
                            size: 'compact',
                        })}
                    >
                        Previous
                    </Link>
                ) : (
                    <Button variant="outline" size="compact" disabled>
                        Previous
                    </Button>
                )}
                {members.links.next ? (
                    <Link
                        href={members.links.next}
                        preserveScroll
                        className={buttonVariants({
                            variant: 'outline',
                            size: 'compact',
                        })}
                    >
                        Next
                    </Link>
                ) : (
                    <Button variant="outline" size="compact" disabled>
                        Next
                    </Button>
                )}
            </div>
        </nav>
    );
}
