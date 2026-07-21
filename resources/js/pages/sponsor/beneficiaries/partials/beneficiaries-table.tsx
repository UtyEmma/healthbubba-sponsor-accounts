import {
    EllipsisIcon,
    HeadsetIcon,
    MailIcon,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type BeneficiaryStatus = 'Active' | 'Pending' | 'Inactive';

export type Beneficiary = {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: BeneficiaryStatus;
    joined: string;
    allocations: string;
    avatar: string;
};

const statusVariants = {
    Active: 'success',
    Pending: 'warning',
    Inactive: 'destructive',
} as const;

export function BeneficiariesTable({
    beneficiaries,
    onAction,
}: {
    beneficiaries: Beneficiary[];
    onAction: (message: string) => void;
}) {
    return (
        <Card className="overflow-hidden">
            <div className="flex h-14 items-center border-b border-border px-6">
                <h2 className="text-base leading-6 font-semibold">
                    All beneficiaries ({beneficiaries.length})
                </h2>
            </div>
            <div className="overflow-x-auto">
                <Table className="min-w-full">
                    <TableHeader>
                        <TableRow className="hover:bg-muted/40">
                            <TableHead className="pl-8">
                                Beneficiary
                            </TableHead>
                            <TableHead >Contact</TableHead>
                            <TableHead >Status</TableHead>
                            <TableHead >
                                Date added
                            </TableHead>
                            <TableHead >
                                Consultations
                            </TableHead>
                            <TableHead className="pr-8 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {beneficiaries.map((beneficiary) => (
                            <TableRow key={beneficiary.id}>
                                <TableCell className="pl-8">
                                    <div className="flex items-center gap-2">
                                        <span className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-success-muted text-sm font-semibold text-secondary-foreground">
                                            <img
                                                src={beneficiary.avatar}
                                                alt=""
                                                className="size-full object-cover"
                                            />
                                            <span className="sr-only">
                                                {beneficiary.name}
                                            </span>
                                        </span>
                                        <span className="font-medium whitespace-nowrap">
                                            {beneficiary.name}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="grid gap-2 text-muted-foreground">
                                        <span className="flex items-center gap-1">
                                            <MailIcon className="size-5" />
                                            {beneficiary.email}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <HeadsetIcon className="size-5" />
                                            {beneficiary.phone}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            statusVariants[beneficiary.status]
                                        }
                                    >
                                        {beneficiary.status}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {beneficiary.joined}
                                </TableCell>
                                <TableCell>
                                    {beneficiary.allocations}
                                </TableCell>
                                <TableCell className="pr-8 text-right">
                                    <BeneficiaryActions
                                        beneficiary={beneficiary}
                                        onAction={onAction}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </Card>
    );
}

function BeneficiaryActions({
    beneficiary,
    onAction,
}: {
    beneficiary: Beneficiary;
    onAction: (message: string) => void;
}) {
    const isPending = beneficiary.status === 'Pending';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label={`Actions for ${beneficiary.name}`}
                    />
                }
            >
                <EllipsisIcon className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-[163px]">
                {isPending ? (
                    <>
                        <DropdownMenuItem
                            onClick={() =>
                                onAction(
                                    `Invitation resent to ${beneficiary.email}`,
                                )
                            }
                        >
                            Resend invite
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            className="text-destructive data-[highlighted]:bg-destructive-muted"
                            onClick={() =>
                                onAction(
                                    `Invitation for ${beneficiary.name} cancelled`,
                                )
                            }
                        >
                            Cancel invite
                        </DropdownMenuItem>
                    </>
                ) : (
                    <>
                        <DropdownMenuItem
                            onClick={() =>
                                onAction(`Viewing ${beneficiary.name}`)
                            }
                        >
                            View details
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            className="text-destructive data-[highlighted]:bg-destructive-muted"
                            onClick={() =>
                                onAction(
                                    `${beneficiary.name} selected for removal`,
                                )
                            }
                        >
                            Remove
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
