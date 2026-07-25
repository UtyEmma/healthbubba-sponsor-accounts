import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';

const trends = [
    { month: 'Jan', consultations: 140 },
    { month: 'Feb', consultations: 168 },
    { month: 'Mar', consultations: 154 },
    { month: 'Apr', consultations: 194 },
    { month: 'May', consultations: 231 },
    { month: 'Jun', consultations: 210 },
];

const chartConfig = {
    consultations: {
        label: 'Consultations',
        color: '#2dad39',
    },
} satisfies ChartConfig;

export function ConsultationTrendsChart() {
    return (
        <ChartContainer
            config={chartConfig}
            className="[aspect-ratio:auto] h-[260px] w-full"
            initialDimension={{ width: 680, height: 260 }}
            aria-label="Sponsored consultations completed each month"
        >
            <AreaChart
                data={trends}
                margin={{ top: 14, right: 8, bottom: 0, left: -12 }}
            >
                <defs>
                    <linearGradient
                        id="consultation-fill"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="0%"
                            stopColor="var(--color-consultations)"
                            stopOpacity={0.2}
                        />
                        <stop
                            offset="100%"
                            stopColor="var(--color-consultations)"
                            stopOpacity={0}
                        />
                    </linearGradient>
                </defs>
                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                <XAxis
                    dataKey="month"
                    axisLine={false}
                    tickLine={false}
                    tickMargin={10}
                />
                <YAxis
                    axisLine={false}
                    tickLine={false}
                    ticks={[0, 60, 120, 180, 240]}
                    domain={[0, 240]}
                    tickMargin={8}
                />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent indicator="line" />}
                />
                <Area
                    type="monotone"
                    dataKey="consultations"
                    stroke="var(--color-consultations)"
                    strokeWidth={2}
                    fill="url(#consultation-fill)"
                />
            </AreaChart>
        </ChartContainer>
    );
}
