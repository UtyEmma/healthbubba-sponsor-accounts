import { cva, type VariantProps } from 'class-variance-authority';
import type { ButtonHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-control text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50 [&_img]:pointer-events-none [&_img]:shrink-0 [&_svg]:pointer-events-none [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
                secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                outline: 'border border-border bg-background text-ink shadow-control hover:bg-accent',
                muted: 'bg-muted text-foreground hover:bg-border',
                ghost: 'text-muted-foreground hover:bg-accent hover:text-foreground',
                destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
            },
            size: {
                default: 'h-10 px-4',
                compact: 'h-8 px-3 text-[13px]',
                sm: 'h-9 px-3',
                lg: 'h-11 px-5',
                icon: 'size-8 p-0',
            },
        },
        defaultVariants: { variant: 'primary', size: 'default' },
    },
);

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants>;

export function Button({ className, variant, size, ...props }: ButtonProps) {
    return (
        <button
            className={cn(
                buttonVariants({ variant, size }),
                className,
            )}
            {...props}
        />
    );
}

export { buttonVariants };
