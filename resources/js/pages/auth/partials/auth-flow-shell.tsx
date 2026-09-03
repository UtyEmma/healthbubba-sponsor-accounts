import type { ReactNode } from 'react';

import { BrandMark } from '@/components/brand-mark';
import { cn } from '@/lib/utils';

interface AuthFlowShellProps {
    title: string;
    description: string;
    step: number;
    totalSteps: number;
    stepLabel: string;
    children: ReactNode;
    wide?: boolean;
    showProgress?: boolean;
}

export function AuthFlowShell({
    title,
    description,
    step,
    totalSteps,
    stepLabel,
    children,
    wide = false,
    showProgress = true,
}: AuthFlowShellProps) {
    return (
        <main className="relative min-h-screen overflow-hidden bg-white px-5 py-12 sm:px-8">
            <img
                src="/images/sponsor/login-bg.svg"
                alt=""
                className="pointer-events-none absolute inset-x-0 bottom-0 h-[53%] w-full object-cover"
            />
            <div
                className={cn(
                    'relative mx-auto flex w-full flex-col items-center sm:pt-[70px]',
                    wide ? 'max-w-xl' : 'max-w-96',
                )}
            >
                <BrandMark />
                <header className="mt-3 text-center">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        {description}
                    </p>
                </header>

                <div
                    className={cn(
                        'mt-4 w-full',
                        wide ? 'max-w-xl' : 'max-w-96',
                    )}
                >
                    {showProgress && (
                        <>
                            <div
                                className="grid gap-1.5"
                                style={{
                                    gridTemplateColumns: `repeat(${totalSteps}, minmax(0, 1fr))`,
                                }}
                                aria-label={`Step ${step} of ${totalSteps}`}
                            >
                                {Array.from(
                                    { length: totalSteps },
                                    (_, index) => index + 1,
                                ).map((segment) => (
                                    <span
                                        key={segment}
                                        className={cn(
                                            'h-1 rounded-full',
                                            segment < step
                                                ? 'bg-success'
                                                : segment === step
                                                  ? 'bg-primary'
                                                  : 'bg-muted',
                                        )}
                                    />
                                ))}
                            </div>
                            <p className="mt-2 text-xs text-muted-foreground">
                                Step {step} of {totalSteps} — {stepLabel}
                            </p>
                        </>
                    )}
                    {children}
                </div>
            </div>
        </main>
    );
}
