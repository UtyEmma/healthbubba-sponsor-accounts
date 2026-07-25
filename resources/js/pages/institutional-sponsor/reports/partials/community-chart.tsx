import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';

const data = [
    { community: 'Sabon Gari', consultations: 312 },
    { community: 'Ogui', consultations: 287 },
    { community: 'Tudun Wada', consultations: 143 },
];

const config = {
    consultations: { label: 'Consultations', color: '#3dbd3b' },
} satisfies ChartConfig;

export function CommunityChart() {
    return (
        <ChartContainer
            config={config}
            className="[aspect-ratio:auto] h-[240px] w-full"
            initialDimension={{ width: 900, height: 240 }}
        >
            <BarChart
                data={data}
                margin={{ top: 16, right: 8, bottom: 0, left: -12 }}
            >
                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                <XAxis
                    dataKey="community"
                    axisLine={false}
                    tickLine={false}
                    tickMargin={8}
                />
                <YAxis
                    axisLine={false}
                    tickLine={false}
                    ticks={[0, 80, 160, 240, 320]}
                    domain={[0, 320]}
                />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent />}
                />
                <Bar
                    dataKey="consultations"
                    fill="var(--color-consultations)"
                    radius={[4, 4, 0, 0]}
                />
            </BarChart>
        </ChartContainer>
    );
}
