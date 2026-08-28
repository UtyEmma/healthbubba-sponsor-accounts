import { Head, Link } from '@inertiajs/react';
import { PartyPopperIcon } from 'lucide-react';

import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import institutional from '@/routes/institutional';

import { InstitutionalRegistrationShell } from './partials/institutional-registration-shell';

const nextSteps = [
    'Fund your account',
    'Start a campaign',
    'Allocate healthcare benefits',
    'Enroll beneficiaries',
    'Track healthcare usage',
];

export default function InstitutionalRegistrationCompleted() {
    return (
        <>
            <Head title="Institutional Sponsor Account Ready" />
            <InstitutionalRegistrationShell
                step={4}
                stepLabel="Account created"
                showIntroduction={false}
                contentClassName="sm:mt-3"
            >
                <div className="pt-5">
                    <h1 className="text-2xl leading-8 font-semibold tracking-[-.025em]">
                        You are all set
                    </h1>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        Everything below is ready to go.
                    </p>
                </div>

                <div className="mt-5 flex size-12 items-center justify-center rounded-full bg-success-muted text-success">
                    <PartyPopperIcon className="size-6" strokeWidth={1.75} />
                </div>

                <div className="mt-5">
                    <h2 className="text-base leading-6 font-semibold">
                        Your Institutional Sponsor Account is ready.
                    </h2>
                    <p className="mt-1 max-w-sm text-sm leading-5 text-muted-foreground">
                        Fund your wallet, start a campaign and enroll the
                        communities you serve.
                    </p>
                </div>

                <ol className="mt-4 overflow-hidden rounded-card border border-border bg-background">
                    {nextSteps.map((step, index) => (
                        <li
                            key={step}
                            className="flex min-h-[46px] items-center gap-3 border-b border-border px-3 last:border-b-0"
                        >
                            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                {index + 1}
                            </span>
                            <span className="text-sm">{step}</span>
                        </li>
                    ))}
                </ol>

                <Link
                    href={institutional.dashboard().url}
                    className={cn(buttonVariants(), 'mt-3 w-full')}
                >
                    Go to Dashboard
                </Link>
            </InstitutionalRegistrationShell>
        </>
    );
}
