import { CreditCardIcon, WalletIcon } from 'lucide-react';

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
import type {
    ConsultationAllocationSummary,
    ConsultationCoverage,
    ConsultationQuotaBreakdown,
} from '@/types';

export function ConsultationOverview({
    coverage,
}: {
    coverage: ConsultationCoverage;
}) {
    const { scaling } = coverage;

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
                        {currentPoolDescription(coverage)}
                    </p>
                </CardHeader>
                <CardContent className="grid gap-5 px-6 pt-3 pb-6">
                    {coverage.allocations.map((allocation) => (
                        <PoolProgress
                            key={allocation.type}
                            allocation={allocation}
                        />
                    ))}
                </CardContent>
            </Card>

            <Card className="min-h-[319px] overflow-hidden">
                <CardHeader className="px-6 pt-6 pb-4">
                    <CardTitle className="text-base leading-5">
                        Allocation scaling
                    </CardTitle>
                    <p className="text-sm leading-5 text-muted-foreground">
                        {scaling.description}
                    </p>
                </CardHeader>
                <div className="overflow-x-auto">
                    <Table className="min-w-[560px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-8">
                                    {scaling.capacityLabel}
                                </TableHead>
                                <TableHead>GP pool</TableHead>
                                <TableHead>Specialist pool</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {scaling.available ? (
                                scaling.steps.map((step) => (
                                    <TableRow key={step.capacity}>
                                        <TableCell className="h-12 pl-8 font-medium">
                                            {step.capacity}{' '}
                                            {step.capacity === 1
                                                ? scaling.capacityUnit
                                                : scaling.capacityUnitPlural}
                                        </TableCell>
                                        <TableCell className="h-12 text-muted-foreground">
                                            {formatQuotaBreakdown(
                                                step.gp,
                                                'GP',
                                            )}
                                        </TableCell>
                                        <TableCell className="h-12 text-muted-foreground">
                                            {formatQuotaBreakdown(
                                                step.specialist,
                                                'specialist',
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="h-12 pl-8 text-muted-foreground"
                                    >
                                        {scaling.unavailableReason}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </section>
    );
}

function PoolProgress({
    allocation,
}: {
    allocation: ConsultationAllocationSummary;
}) {
    const used = allocation.completed + allocation.reserved;
    const percentage =
        allocation.limit === null
            ? 0
            : allocation.limit === 0
              ? 0
              : Math.min(100, (used / allocation.limit) * 100);
    const remaining =
        allocation.remaining === null ? 'Unlimited' : allocation.remaining;
    const limit = allocation.limit === null ? 'Unlimited' : allocation.limit;

    return (
        <div>
            <div className="flex items-center justify-between gap-4 text-sm leading-5">
                <span>{allocation.label}</span>
                <strong className="font-semibold">
                    {used}/{limit} used
                </strong>
            </div>
            <Progress
                value={percentage}
                aria-label={`${allocation.label}: ${remaining}`}
                className="mt-1.5 h-2.5"
            />
        </div>
    );
}

function currentPoolDescription(coverage: ConsultationCoverage): string {
    const currentCapacity = coverage.scaling.currentCapacity;

    if (currentCapacity === null) {
        return `Shared across ${coverage.activeBeneficiaries} active beneficiaries`;
    }

    const activeUnit =
        coverage.scaling.capacityUnit === 'employee seat'
            ? coverage.activeBeneficiaries === 1
                ? 'employee'
                : 'employees'
            : coverage.activeBeneficiaries === 1
              ? 'beneficiary'
              : 'beneficiaries';
    const capacityUnit =
        currentCapacity === 1
            ? coverage.scaling.capacityUnit
            : coverage.scaling.capacityUnitPlural;

    return `${currentCapacity} ${capacityUnit} allocated · ${coverage.activeBeneficiaries} active ${activeUnit}`;
}

function formatQuotaBreakdown(
    breakdown: ConsultationQuotaBreakdown,
    label: string,
): string {
    if (breakdown.total === null) {
        return `Unlimited ${label}`;
    }

    return `${breakdown.total} ${label} (${breakdown.base ?? 0} base + ${breakdown.additional ?? 0} additional)`;
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
                <CardTitle className="text-base leading-5">
                    When allocations run out
                </CardTitle>
                <p className="text-sm leading-5 text-muted-foreground">
                    Care isn’t blocked; beneficiaries unlock direct checkout
                    via:
                </p>
            </CardHeader>

            <CardContent className="grid gap-3 px-6 pb-6">
                {options.map(({ icon: Icon, title, description }) => (
                    <div
                        key={title}
                        className="flex items-center gap-3 rounded-xl border border-border p-4"
                    >
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-muted text-success">
                            <Icon className="size-5" />
                        </span>
                        <div>
                            <h3 className="leading-5 font-medium">{title}</h3>
                            <p className="text-sm leading-4 text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
