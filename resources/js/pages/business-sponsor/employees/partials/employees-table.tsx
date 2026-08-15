import { RosterPagination } from '@/components/roster-pagination';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

export function EmployeesTable({
    invitations,
    canManage,
}: {
    invitations: PaginatedWorkspaceBeneficiaries;
    canManage: boolean;
}) {
    return (
        <Card className="mt-5 overflow-hidden">
            <CardHeader className="h-14 justify-center border-b px-6 py-0">
                <CardTitle className="text-base">
                    All employees ({invitations.meta.total})
                </CardTitle>
            </CardHeader>
            <CardContent className="overflow-x-auto p-0">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow className="hover:bg-muted/40">
                            <TableHead className="pl-8">Employee</TableHead>
                            <TableHead>ID</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Invitation</TableHead>
                            {canManage && (
                                <TableHead className="pr-8 text-right">
                                    Actions
                                </TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {invitations.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={canManage ? 6 : 5}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    No employees have been invited yet.
                                </TableCell>
                            </TableRow>
                        ) : (
                            invitations.data.map((employee) => (
                                <TableRow key={employee.id}>
                                    <TableCell className="h-[69px] pl-8">
                                        <div className="flex items-center gap-2">
                                            <Avatar size="lg">
                                                <AvatarFallback>
                                                    {initials(employee)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="grid leading-5">
                                                <span className="font-medium">
                                                    {employee.name}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {employee.email}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {employee.employeeId ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {employee.department ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                statusVariants[employee.status]
                                            }
                                            className="capitalize"
                                        >
                                            {employee.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {employee.status === 'pending'
                                            ? `Expires ${formatDate(employee.expiresAt)}`
                                            : formatDate(employee.invitedAt)}
                                    </TableCell>
                                    {canManage && (
                                        <TableCell className="pr-8 text-right">
                                            <WorkspaceInvitationActions
                                                invitation={employee}
                                            />
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </CardContent>
            <RosterPagination pagination={invitations} />
        </Card>
    );
}

function initials(employee: WorkspaceBeneficiary): string {
    return `${employee.firstName.charAt(0)}${employee.lastName.charAt(0)}`.toUpperCase();
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
