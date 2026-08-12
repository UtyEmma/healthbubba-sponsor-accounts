import { MailIcon, PhoneIcon } from 'lucide-react';

import { RosterPagination } from '@/components/roster-pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { WorkspaceInvitationActions } from '@/components/workspace-invitation-actions';
import type {
    PaginatedWorkspaceBeneficiaries,
    WorkspaceBeneficiary,
    WorkspaceBeneficiaryStatus,
} from '@/types';

const statusVariants: Record<
    WorkspaceBeneficiaryStatus,
    'success' | 'warning' | 'destructive' | 'secondary'
> = {
    active: 'success',
    suspended: 'warning',
    revoked: 'destructive',
    pending: 'warning',
    declined: 'destructive',
    cancelled: 'secondary',
    expired: 'secondary',
};

export function BeneficiariesTable({
    invitations,
}: {
    invitations: PaginatedWorkspaceBeneficiaries;
}) {
    return (
        <Card className="overflow-hidden">
            <div className="flex h-14 items-center border-b px-6">
                <h2 className="text-base leading-6 font-semibold">
                    All beneficiaries ({invitations.meta.total})
                </h2>
            </div>
            <div className="overflow-x-auto">
                <Table className="min-w-[860px]">
                    <TableHeader>
                        <TableRow className="hover:bg-muted/40">
                            <TableHead className="pl-8">Beneficiary</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Invitation</TableHead>
                            <TableHead className="pr-8 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {invitations.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    No beneficiaries have been invited yet.
                                </TableCell>
                            </TableRow>
                        ) : (
                            invitations.data.map((invitation) => (
                                <TableRow key={invitation.id}>
                                    <TableCell className="pl-8">
                                        <div className="flex items-center gap-3">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-success-muted text-sm font-semibold text-success">
                                                {initials(invitation)}
                                            </span>
                                            <div>
                                                <p className="font-medium whitespace-nowrap">
                                                    {invitation.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {invitation.hasHealthBubbaAccount
                                                        ? 'HealthBubba account found'
                                                        : 'New invitee'}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="grid gap-1 text-muted-foreground">
                                            <span className="flex items-center gap-1.5">
                                                <MailIcon className="size-4" />
                                                {invitation.email}
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <PhoneIcon className="size-4" />
                                                {invitation.phone}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                statusVariants[
                                                    invitation.status
                                                ]
                                            }
                                            className="capitalize"
                                        >
                                            {invitation.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {invitation.status === 'pending'
                                            ? `Expires ${formatDate(invitation.expiresAt)}`
                                            : `Invited ${formatDate(invitation.invitedAt)}`}
                                    </TableCell>
                                    <TableCell className="pr-8 text-right">
                                        <WorkspaceInvitationActions
                                            invitation={invitation}
                                        />
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
            <RosterPagination pagination={invitations} />
        </Card>
    );
}

function initials(invitation: WorkspaceBeneficiary): string {
    return `${invitation.firstName.charAt(0)}${invitation.lastName.charAt(0)}`.toUpperCase();
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
