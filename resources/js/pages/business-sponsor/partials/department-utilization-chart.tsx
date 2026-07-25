import { Bar, BarChart, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';

const utilization = [
    { department: 'Operations', gp: 4, specialist: 6 },
    { department: 'Finance', gp: 1, specialist: 3 },
    { department: 'Logistics', gp: 3, specialist: 2 },
    { department: 'HR', gp: 1, specialist: 5 },
    { department: 'Sales', gp: 1, specialist: 1 },
];

const chartConfig = {
    gp: { label: 'GP consultations', color: '#2f66df' },
    specialist: { label: 'Specialist consultations', color: '#2ac17e' },
} satisfies ChartConfig;

export function DepartmentUtilizationChart() {
    return (
        <ChartContainer
            config={chartConfig}
            className="[aspect-ratio:auto] h-[220px] w-full justify-start"
            initialDimension={{ width: 700, height: 220 }}
            aria-label="Consultation utilization by department"
        >
            <BarChart
                data={utilization}
                margin={{ top: 12, right: 12, bottom: 0, left: -12 }}
            >
                <XAxis
                    dataKey="department"
                    axisLine={false}
                    tickLine={false}
                    tickMargin={12}
                    interval={0}
                />
                <YAxis
                    axisLine={false}
                    tickLine={false}
                    ticks={[0, 4, 8, 16]}
                    domain={[0, 16]}
                    tickMargin={8}
                />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent />}
                />
                <Bar
                    dataKey="gp"
                    stackId="consults"
                    fill="var(--color-gp)"
                    maxBarSize={43}
                />
                <Bar
                    dataKey="specialist"
                    stackId="consults"
                    fill="var(--color-specialist)"
                    maxBarSize={43}
                />
            </BarChart>
        </ChartContainer>
    );
}
