import { ChevronDownIcon } from 'lucide-react';
import { forwardRef } from 'react';
import type { ComponentPropsWithoutRef } from 'react';

import { cn } from '@/lib/utils';

type SelectProps = ComponentPropsWithoutRef<'select'> & {
    containerClassName?: string;
};

const Select = forwardRef<HTMLSelectElement, SelectProps>(
    ({ className, containerClassName, children, ...props }, ref) => (
        <span
            data-slot="native-select-wrapper"
            className={cn('relative flex w-full', containerClassName)}
        >
            <select
                ref={ref}
                data-slot="native-select"
                className={cn(
                    'h-10 w-full appearance-none rounded-control border border-input bg-background px-3 pr-9 text-sm text-foreground outline-none transition-shadow focus:border-ring focus:ring-2 focus:ring-ring/20 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDownIcon
                aria-hidden="true"
                className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
        </span>
    ),
);

Select.displayName = 'Select';

export { Select };
