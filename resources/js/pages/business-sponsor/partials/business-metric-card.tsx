import type { ComponentType, SVGProps } from 'react';

import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function BusinessMetricCard({
    label,
    value,
    icon: Icon,
    tone = 'neutral',
}: {
    label: string;
    value: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    tone?: 'green' | 'blue' | 'amber' | 'neutral';
}) {
    return (
        <Card className="min-h-[94px]">
            <CardContent className="flex h-full items-center gap-4 p-5">
                <span className="flex size-12 shrink-0 items-center justify-center rounded-xl border border-border bg-background shadow-control">
                    <Icon
                        className={cn(
                            'size-5',
                            tone === 'green' && 'text-success',
                            tone === 'blue' && 'text-blue-600',
                            tone === 'amber' && 'text-amber-500',
                        )}
                    />
                </span>
                <div>
                    <p className="text-sm leading-5 text-muted-foreground">
                        {label}
                    </p>
                    <p className="text-2xl leading-8 font-semibold tracking-[-0.4px]">
                        {value}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
