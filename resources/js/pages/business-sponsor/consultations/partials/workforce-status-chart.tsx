import { Cell, Pie, PieChart } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';

const workforce = [
    { status: 'Active', value: 50, amount: '$36,638,465.14', color: '#18b7a6' },
    {
        status: 'Inactive',
        value: 16.67,
        amount: '$8,141,881.2',
        color: '#ff120b',
    },
    {
        status: 'Pending',
        value: 16.67,
        amount: '$4,070,940.6',
        color: '#ff9d00',
    },
    {
        status: 'Suspended',
        value: 16.66,
        amount: '$12,212,821.83',
        color: '#f30065',
    },
];

const chartConfig = Object.fromEntries(
    workforce.map(({ status, color }) => [status, { label: status, color }]),
) satisfies ChartConfig;

export function WorkforceStatusChart() {
    return (
        <div className="flex flex-col items-center gap-5 sm:flex-row sm:justify-center lg:justify-start">
            <ChartContainer
                config={chartConfig}
                className="[aspect-ratio:auto] h-[176px] w-[176px] shrink-0"
                initialDimension={{ width: 176, height: 176 }}
                aria-label="Employee lifecycle breakdown"
            >
                <PieChart>
                    <ChartTooltip
                        cursor={false}
                        content={
                            <ChartTooltipContent hideLabel nameKey="status" />
                        }
                    />
                    <Pie
                        data={workforce}
                        dataKey="value"
                        nameKey="status"
                        innerRadius={25}
                        outerRadius={80}
                        strokeWidth={0}
                    >
                        {workforce.map((entry) => (
                            <Cell key={entry.status} fill={entry.color} />
                        ))}
                    </Pie>
                </PieChart>
            </ChartContainer>

            <ul className="grid w-full min-w-0 gap-3 text-sm">
                {workforce.map((entry) => (
                    <li key={entry.status} className="flex items-center gap-3">
                        <span
                            className="size-3 shrink-0 rounded-full"
                            style={{ backgroundColor: entry.color }}
                        />
                        <span className="text-muted-foreground">
                            {entry.status}
                        </span>
                        <strong className="ml-auto text-[13px] font-semibold whitespace-nowrap">
                            {entry.amount}
                        </strong>
                    </li>
                ))}
            </ul>
        </div>
    );
}
