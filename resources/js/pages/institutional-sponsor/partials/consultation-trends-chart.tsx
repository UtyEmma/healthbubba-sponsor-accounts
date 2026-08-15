import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import type { DashboardConsultationTrend } from '@/types';

const chartConfig = {
    consultations: {
        label: 'Consultations',
        color: '#2dad39',
    },
} satisfies ChartConfig;

export function ConsultationTrendsChart({
    data,
}: {
    data: DashboardConsultationTrend[];
}) {
    const ticks = chartTicks(
        Math.max(0, ...data.map((item) => item.consultations)),
    );

    return (
        <ChartContainer
            config={chartConfig}
            className="[aspect-ratio:auto] h-[260px] w-full"
            initialDimension={{ width: 680, height: 260 }}
            aria-label="Sponsored consultations completed each month"
        >
            <AreaChart
                data={data}
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
                    ticks={ticks}
                    domain={[0, ticks.at(-1) ?? 4]}
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

function chartTicks(maximum: number): number[] {
    const ceiling = Math.max(4, Math.ceil(maximum / 4) * 4);

    return [0, ceiling / 4, ceiling / 2, (ceiling * 3) / 4, ceiling];
}
