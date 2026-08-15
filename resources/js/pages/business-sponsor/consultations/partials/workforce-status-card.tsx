import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { WorkforceStatusSummary } from '@/types';

import { WorkforceStatusChart } from './workforce-status-chart';

export function WorkforceStatusCard({
    workforce,
}: {
    workforce: WorkforceStatusSummary[];
}) {
    return (
        <Card>
            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                <CardTitle className="text-base font-semibold">
                    Workforce status
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    Employee lifecycle breakdown
                </p>
            </CardHeader>
            <CardContent className="px-6 pt-5 pb-8">
                <WorkforceStatusChart workforce={workforce} />
            </CardContent>
        </Card>
    );
}
