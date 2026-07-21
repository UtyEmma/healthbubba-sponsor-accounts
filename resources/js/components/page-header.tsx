import type { ReactNode } from 'react';

export function PageHeader({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <header className="flex min-h-14 flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 className="text-2xl leading-8 font-semibold tracking-[-0.6px] text-foreground">
                    {title}
                </h1>
                <p className="pt-1 text-sm leading-5 text-muted-foreground">
                    {description}
                </p>
            </div>
            {action}
        </header>
    );
}
