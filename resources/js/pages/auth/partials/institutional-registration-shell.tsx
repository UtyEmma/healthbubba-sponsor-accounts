import type { ReactNode } from 'react';

import { BrandMark } from '@/components/brand-mark';
import { cn } from '@/lib/utils';

interface InstitutionalRegistrationShellProps {
    step: number;
    totalSteps?: number;
    stepLabel: string;
    children: ReactNode;
    showIntroduction?: boolean;
    contentClassName?: string;
}

export function InstitutionalRegistrationShell({
    step,
    totalSteps = 4,
    stepLabel,
    children,
    showIntroduction = true,
    contentClassName,
}: InstitutionalRegistrationShellProps) {
    return (
        <main className="relative min-h-screen overflow-hidden bg-white px-5 py-12 sm:px-8">
            <img
                src="/images/sponsor/login-bg.svg"
                alt=""
                className="pointer-events-none absolute inset-x-0 bottom-0 h-[53%] w-full object-cover"
            />

            <div className="relative mx-auto flex w-full max-w-xl flex-col items-center sm:pt-[70px]">
                <BrandMark />

                {showIntroduction && (
                    <header className="mt-[13px] text-center">
                        <h1 className="text-2xl leading-8 font-semibold tracking-[-.025em] whitespace-nowrap max-sm:whitespace-normal">
                            Create your Institutional sponsor account
                        </h1>
                        <p className="text-sm leading-6 text-muted-foreground">
                            Create and start sponsoring healthcare
                        </p>
                    </header>
                )}

                <div
                    className={cn(
                        'w-full max-w-96',
                        showIntroduction ? 'mt-3' : 'mt-3',
                        contentClassName,
                    )}
                >
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
                                    segment <= step
                                        ? segment === step
                                            ? 'bg-secondary'
                                            : 'bg-success'
                                        : 'bg-muted',
                                )}
                            />
                        ))}
                    </div>

                    <p className="mt-2 text-xs leading-4 text-muted-foreground">
                        Step {step} of {totalSteps} — {stepLabel}
                    </p>

                    {children}
                </div>
            </div>
        </main>
    );
}
