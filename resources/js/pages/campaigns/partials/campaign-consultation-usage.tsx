import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import type {
    ConsultationAllocationSummary,
    ConsultationCoverage,
} from '@/types';

export function CampaignConsultationUsage({
    coverage,
}: {
    coverage: ConsultationCoverage;
}) {
    return (
        <section aria-label="Consultation usage">
            <Card>
                <CardHeader className="gap-1">
                    <CardTitle className="text-base">Coverage wallet</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        Purchased, consumed and remaining units.
                    </p>
                </CardHeader>
                <CardContent className="space-y-5 pt-0">
                    {coverage.allocations.map((allocation) => (
                        <AllocationState
                            key={allocation.type}
                            allocation={allocation}
                        />
                    ))}
                </CardContent>
            </Card>
        </section>
    );
}

function AllocationState({
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
    const limit = allocation.limit === null ? 'Unlimited' : allocation.limit;
    const remaining =
        allocation.remaining === null ? 'Unlimited' : allocation.remaining;

    return (
        <div className="space-y-1">
            <div className="flex items-center justify-between text-sm">
                <span>{allocation.label}</span>
                <strong className="font-medium">
                    {remaining} / {limit} left
                </strong>
            </div>
            <Progress
                value={percentage}
                aria-label={`${allocation.label}: ${used} of ${limit} used`}
                className="h-2.5"
            />
        </div>
    );
}
