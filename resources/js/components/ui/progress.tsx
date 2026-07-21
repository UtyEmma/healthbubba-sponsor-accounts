import { Progress as ProgressPrimitive } from '@base-ui/react/progress';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Progress({ className, value = 0, ...props }: ComponentProps<typeof ProgressPrimitive.Root>) {
    return (
        <ProgressPrimitive.Root value={value} className={cn('h-2.5 w-full overflow-hidden rounded-full bg-muted', className)} {...props}>
            <ProgressPrimitive.Track className="h-full w-full">
                <ProgressPrimitive.Indicator className="h-full min-w-1 rounded-full bg-success transition-transform" />
            </ProgressPrimitive.Track>
        </ProgressPrimitive.Root>
    );
}
