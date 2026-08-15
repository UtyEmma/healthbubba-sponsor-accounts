import { Bar, BarChart, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import type { DashboardDepartmentUtilization } from '@/types';

const chartConfig = {
    gp: { label: 'GP consultations', color: '#2f66df' },
    specialist: { label: 'Specialist consultations', color: '#2ac17e' },
} satisfies ChartConfig;

export function DepartmentUtilizationChart({
    data,
}: {
    data: DashboardDepartmentUtilization[];
}) {
    const ticks = chartTicks(
        Math.max(0, ...data.map((row) => row.gp + row.specialist)),
    );

    return (
        <ChartContainer
            config={chartConfig}
            className="[aspect-ratio:auto] h-[220px] w-full justify-start"
            initialDimension={{ width: 700, height: 220 }}
            aria-label="Consultation utilization by department"
        >
            <BarChart
                data={data}
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
                    ticks={ticks}
                    domain={[0, ticks.at(-1) ?? 4]}
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

function chartTicks(maximum: number): number[] {
    const ceiling = Math.max(4, Math.ceil(maximum / 4) * 4);

    return [0, ceiling / 4, ceiling / 2, (ceiling * 3) / 4, ceiling];
}
