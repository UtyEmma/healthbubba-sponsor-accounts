import { CreditCardIcon, WalletCardsIcon, WalletIcon } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ConsultationAllocationSummary, ConsultationCoverage } from '@/types';

const allocations = [
    ['3 users', '5 GP', '2 specialist'],
    ['4 users', '7 GP', '2 specialist'],
    ['5 users', '9 GP', '2 specialist'],
    ['5 users', '11 GP', '2 specialist'],
] as const;

export function ConsultationOverview({coverage}: {coverage: ConsultationCoverage}) {
    return (
        <section
            className="grid gap-4 pt-6 lg:grid-cols-[416px_minmax(0,1fr)]"
            aria-label="Consultation allocation overview"
        >
            <Card className="min-h-[319px]">
                <CardHeader className="px-6 pt-7 pb-3">
                    <CardTitle className="text-base leading-5">
                        Current pool
                    </CardTitle>
                    <p className="text-sm leading-5 text-muted-foreground">
                        Shared across 4 active beneficiaries
                    </p>
                </CardHeader>
                <CardContent className="grid gap-5 px-6 pt-3 pb-6">
                    {
                        coverage.allocations.map(allocation => (
                            <PoolProgress
                                allocation={allocation}
                            />
                        ))
                    }
                </CardContent>
            </Card>

            <Card className="min-h-[319px] overflow-hidden">
                <CardHeader className="px-6 pt-6 pb-4">
                    <CardTitle className="text-base leading-5">
                        Allocation scaling
                    </CardTitle>
                    <p className="text-sm leading-5 text-muted-foreground">
                        Each extra beneficiary adds +2 GP consults to the shared
                        pool (specialist count is fixed).
                    </p>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table className="min-w-[560px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-8">
                                    Beneficiaries
                                </TableHead>
                                <TableHead>GP pool</TableHead>
                                <TableHead>Specialist pool</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {allocations.map(([users, gp, specialist]) => (
                                <TableRow key={`${users}-${gp}`}>
                                    <TableCell className="h-12 pl-8 font-medium">
                                        {users}
                                    </TableCell>
                                    <TableCell className="h-12 text-muted-foreground">
                                        {gp}
                                    </TableCell>
                                    <TableCell className="h-12 text-muted-foreground">
                                        {specialist}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </section>
    );
}

function PoolProgress({allocation}: {allocation : ConsultationAllocationSummary}) {

    const used = allocation.completed + allocation.reserved;
    const percentage =
        allocation.limit === null
            ? 0
            : allocation.limit === 0
              ? 0
              : Math.min(100, (used / allocation.limit) * 100);
    const remaining =
        allocation.remaining === null ? 'Unlimited' : allocation.remaining;

    return (
        <div>
            <div className="flex items-center justify-between gap-4 text-sm leading-5">
                <span>{allocation.label}</span>
                <strong className="font-semibold">{used}/{allocation.limit} used</strong>
            </div>
            <Progress
                value={percentage}
                aria-label={`${allocation.label}: ${remaining}`}
                className="mt-1.5 h-2.5"
            />
        </div>
    );
}

export function AllocationFallbackCard() {
    const options = [
        {
            icon: WalletIcon,
            title: 'Beneficiary wallet',
            description: 'Paid from their own balance',
        },
        {
            icon: CreditCardIcon,
            title: 'Card payment',
            description: 'Direct external checkout',
        },
    ];

    return (
        <Card className="mt-5">
            <CardHeader className="px-6 pt-7 pb-5">
                <CardTitle className="text-base leading-5">When allocations run out</CardTitle>
                <p className="text-sm leading-5 text-muted-foreground">Care isn’t blocked; beneficiaries unlock direct checkout via:</p>
            </CardHeader>

            <CardContent className="grid gap-3 px-6 pb-6">
                {options.map(({ icon: Icon, title, description }) => (
                    <div key={title} className="flex items-center gap-3 rounded-xl border border-border p-4" >
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-muted text-success">
                            <Icon className="size-5" />
                        </span>
                        <div>
                            <h3 className="leading-5 font-medium">{title}</h3>
                            <p className="text-sm leading-4 text-muted-foreground">{description}</p>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
