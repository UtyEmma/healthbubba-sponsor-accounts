import { StethoscopeIcon, UsersRoundIcon } from 'lucide-react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import type {
    ConsultationAllocationSummary,
    ConsultationCoverage,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
});

export function ConsultationOverview({
    coverage,
}: {
    coverage: ConsultationCoverage;
}) {
    return (
        <section className="pt-6" aria-label="Consultation allocation overview">
            <div className="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 className="text-base font-semibold">
                        Current allocation
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {coverage.planName ?? 'No active plan'}
                    </p>
                </div>
                <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    <UsersRoundIcon className="size-4" />
                    {coverage.activeBeneficiaries} active{' '}
                    {coverage.activeBeneficiaries === 1
                        ? 'beneficiary'
                        : 'beneficiaries'}
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                {coverage.allocations.map((allocation) => (
                    <AllocationCard
                        key={allocation.type}
                        allocation={allocation}
                    />
                ))}
            </div>
        </section>
    );
}

function AllocationCard({
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

    return (
        <Card>
            <CardHeader className="flex-row items-start justify-between gap-4 pb-4">
                <div className="grid gap-1.5">
                    <CardTitle className="text-base">
                        {allocation.label}
                    </CardTitle>
                    <CardDescription>{allocation.scopeLabel}</CardDescription>
                </div>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-muted text-success">
                    <StethoscopeIcon className="size-5" />
                </span>
            </CardHeader>
            <CardContent className="grid gap-4">
                {allocation.unavailableReason ? (
                    <p className="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        {allocation.unavailableReason}
                    </p>
                ) : (
                    <>
                        <div>
                            <div className="flex items-center justify-between gap-4 text-sm">
                                <span className="text-muted-foreground">
                                    Remaining
                                </span>
                                <strong className="font-semibold">
                                    {remaining}
                                    {allocation.limit !== null && (
                                        <span className="font-normal text-muted-foreground">
                                            {' '}
                                            of {allocation.limit}
                                        </span>
                                    )}
                                </strong>
                            </div>
                            <Progress
                                value={percentage}
                                aria-label={`${allocation.label}: ${remaining} remaining`}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <AllocationStat
                                label="Completed"
                                value={allocation.completed}
                            />
                            <AllocationStat
                                label="Reserved"
                                value={allocation.reserved}
                            />
                        </div>

                        {allocation.resetAt && (
                            <p className="text-xs text-muted-foreground">
                                Resets{' '}
                                {dateFormatter.format(
                                    new Date(allocation.resetAt),
                                )}
                            </p>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}

function AllocationStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border border-border px-3 py-2.5">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="pt-0.5 font-semibold">{value}</p>
        </div>
    );
}
