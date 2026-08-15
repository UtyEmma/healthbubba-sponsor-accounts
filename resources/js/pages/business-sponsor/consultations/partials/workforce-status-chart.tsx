import { Cell, Pie, PieChart } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import type { WorkforceStatus, WorkforceStatusSummary } from '@/types';

const colors: Record<WorkforceStatus, string> = {
    active: '#18b7a6',
    inactive: '#ff120b',
    pending: '#ff9d00',
    suspended: '#f30065',
};
const emptyColor = '#d1d5db';

const chartConfig = {
    active: { label: 'Active', color: colors.active },
    inactive: { label: 'Inactive', color: colors.inactive },
    pending: { label: 'Pending', color: colors.pending },
    suspended: { label: 'Suspended', color: colors.suspended },
    empty: { label: 'No employees', color: emptyColor },
} satisfies ChartConfig;

export function WorkforceStatusChart({
    workforce,
}: {
    workforce: WorkforceStatusSummary[];
}) {
    const chartData = workforce.map((entry) => ({
        ...entry,
        color: colors[entry.status],
    }));
    const hasWorkforce = chartData.some(({ count }) => count > 0);
    const pieData = hasWorkforce
        ? chartData
        : [
              {
                  status: 'empty',
                  label: 'No employees',
                  count: 1,
                  percentage: 100,
                  color: emptyColor,
              },
          ];

    return (
        <div className="flex flex-col items-center gap-5 sm:flex-row sm:justify-center lg:justify-start">
            <ChartContainer
                config={chartConfig}
                className="[aspect-ratio:auto] h-[176px] w-[176px] shrink-0"
                initialDimension={{ width: 176, height: 176 }}
                aria-label="Employee lifecycle breakdown"
            >
                <PieChart>
                    {hasWorkforce && (
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    hideLabel
                                    nameKey="status"
                                />
                            }
                        />
                    )}
                    <Pie
                        data={pieData}
                        dataKey="count"
                        nameKey="status"
                        innerRadius={25}
                        outerRadius={80}
                        strokeWidth={0}
                    >
                        {pieData.map((entry) => (
                            <Cell key={entry.status} fill={entry.color} />
                        ))}
                    </Pie>
                </PieChart>
            </ChartContainer>

            <ul className="grid w-full min-w-0 gap-3 text-sm">
                {chartData.map((entry) => (
                    <li key={entry.status} className="flex items-center gap-3">
                        <span
                            className="size-3 shrink-0 rounded-full"
                            style={{ backgroundColor: entry.color }}
                        />
                        <span className="text-muted-foreground">
                            {entry.label}
                        </span>
                        <strong className="ml-auto text-[13px] font-semibold whitespace-nowrap">
                            {entry.count}{' '}
                            {entry.count === 1 ? 'employee' : 'employees'} (
                            {formatPercentage(entry.percentage)})
                        </strong>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function formatPercentage(percentage: number): string {
    return `${Number.isInteger(percentage) ? percentage : percentage.toFixed(1)}%`;
}
