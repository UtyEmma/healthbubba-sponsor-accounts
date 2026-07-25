import { Head } from '@inertiajs/react';
import { UserRoundPlusIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { institutionalNavigation } from '../partials/institutional-navigation';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    active: boolean;
};
const initialMembers: Member[] = [
    {
        id: 1,
        name: 'David Smith',
        email: 'amaka.eze@hopealive.org',
        role: 'Owner',
        active: true,
    },
    {
        id: 2,
        name: 'David Smith',
        email: 'amaka.eze@hopealive.org',
        role: 'Administrator',
        active: true,
    },
    {
        id: 3,
        name: 'David Smith',
        email: 'amaka.eze@hopealive.org',
        role: 'Viewer',
        active: false,
    },
    {
        id: 4,
        name: 'David Smith',
        email: 'amaka.eze@hopealive.org',
        role: 'Viewer',
        active: false,
    },
    {
        id: 5,
        name: 'David Smith',
        email: 'amaka.eze@hopealive.org',
        role: 'Administrator',
        active: true,
    },
];

export default function InstitutionalTeamPage() {
    const [members, setMembers] = useState(initialMembers);
    const [open, setOpen] = useState(false);
    const [announcement, setAnnouncement] = useState('');

    function invite(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const member = {
            id: Date.now(),
            name: String(data.get('name')),
            email: String(data.get('email')),
            role: String(data.get('role')),
            active: true,
        };
        setMembers((items) => [...items, member]);
        setAnnouncement(`Invitation sent to ${member.email}.`);
        setOpen(false);
    }

    return (
        <>
            <Head title="Team Management" />
            <BusinessPortalShell
                navigation={institutionalNavigation}
                navigationLabel="Institutional sponsor navigation"
            >
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Team Management"
                        description="Invite colleagues and control what they can do."
                        action={
                            <Button
                                size="compact"
                                onClick={() => setOpen(true)}
                            >
                                <UserRoundPlusIcon className="size-4" />
                                Invite Member
                            </Button>
                        }
                    />
                    <Card className="mt-4 overflow-hidden">
                        <CardHeader className="h-14 justify-center border-b px-6 py-0">
                            <CardTitle className="text-base">
                                Members ({members.length})
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
                                        <TableHead className="pr-8 text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {members.map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="h-[69px] pl-8">
                                                <span className="block font-medium">
                                                    {member.name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {member.email}
                                                </span>
                                            </TableCell>
                                            <TableCell className="h-[69px] text-muted-foreground">
                                                {member.role}
                                            </TableCell>
                                            <TableCell className="h-[69px]">
                                                <Badge
                                                    variant={
                                                        member.active
                                                            ? 'success'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {member.active
                                                        ? 'Active'
                                                        : 'Disabled'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="h-[69px] pr-8 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="compact"
                                                    className={
                                                        member.active
                                                            ? 'text-destructive'
                                                            : undefined
                                                    }
                                                    onClick={() =>
                                                        setMembers((items) =>
                                                            items.map((item) =>
                                                                item.id ===
                                                                member.id
                                                                    ? {
                                                                          ...item,
                                                                          active: !item.active,
                                                                      }
                                                                    : item,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    {member.active
                                                        ? 'Disable'
                                                        : 'Enable'}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                    <InviteDialog
                        open={open}
                        onOpenChange={setOpen}
                        onSubmit={invite}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
        </>
    );
}

function InviteDialog({
    open,
    onOpenChange,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
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
                <form onSubmit={onSubmit}>
                    <div className="grid gap-4 px-6 py-4">
                        <label className="grid gap-2 text-[13px] font-medium">
                            Full name
                            <Input name="name" required />
                        </label>
                        <label className="grid gap-2 text-[13px] font-medium">
                            Email address
                            <Input name="email" type="email" required />
                        </label>
                        <label className="grid gap-2 text-[13px] font-medium">
                            Role
                            <select
                                name="role"
                                required
                                defaultValue=""
                                className="h-10 rounded-control border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled>
                                    Select
                                </option>
                                <option>Administrator</option>
                                <option>Viewer</option>
                            </select>
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
                        <Button type="submit" size="compact">
                            Send Invitation
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
