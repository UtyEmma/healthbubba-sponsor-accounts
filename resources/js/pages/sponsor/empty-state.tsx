import { Head, Link } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import {
    GettingStartedStep,
    SponsorBenefitCard,
} from './partials/getting-started-cards';
import type { GettingStartedItem } from './partials/getting-started-cards';

const steps: GettingStartedItem[] = [
    {
        title: 'Choose a plan',
        description: 'Pick the coverage that fits your family.',
        icon: 'empty-step-plan.svg',
    },
    {
        title: 'Add beneficiaries',
        description: 'Invite the people you want to cover.',
        icon: 'empty-step-beneficiary.svg',
    },
    {
        title: 'They get care',
        description: 'Beneficiaries book consultations — you track it all.',
        icon: 'empty-step-care.svg',
    },
];

const benefits: GettingStartedItem[] = [
    {
        title: 'Care for the whole family',
        description:
            'Cover parents, children and loved ones under a single sponsorship.',
        icon: 'empty-benefit-family.svg',
    },
    {
        title: 'See a doctor in minutes',
        description:
            'On-demand GP and specialist video consults — no queues, no clinics.',
        icon: 'empty-benefit-doctor.svg',
    },
    {
        title: 'You fund it, they own it',
        description:
            'Beneficiaries keep full privacy and control of their medical records.',
        icon: 'empty-benefit-privacy.svg',
    },
    {
        title: 'Only pay for what you need',
        description:
            'Shared consultation pools, and add beneficiaries whenever you like.',
        icon: 'empty-benefit-payment.svg',
    },
];

export default function EmptyState() {
    return (
        <>
            <Head title="Welcome" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Welcome to Health Bubba, Ada 👋"
                        description="Let's get your family covered — it starts with choosing a plan."
                    />

                    <section className="mt-6 rounded-xl border border-border/70 bg-linear-to-b from-success/10 to-transparent p-px">
                        <div className="flex min-h-[171px] items-center p-6 sm:p-8">
                            <div className="w-full">
                                <h2 className="max-w-[900px] text-[30px] leading-[38px] font-semibold tracking-[-1.5px] text-foreground sm:text-4xl sm:leading-[42px] sm:tracking-[-2px]">
                                    Sponsor quality healthcare for the people
                                    you love
                                </h2>
                                <p className="pt-2 text-sm leading-[21px] text-muted-foreground sm:text-base">
                                    Choose a plan and your family can start
                                    booking GP and specialist consultations
                                    today — while every beneficiary keeps full
                                    control of their own records.
                                </p>
                                <Link
                                    href={home()}
                                    className={cn(
                                        buttonVariants({
                                            variant: 'outline',
                                            size: 'compact',
                                        }),
                                        'mt-5',
                                    )}
                                >
                                    Choose a plan
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section className="pt-6" aria-labelledby="how-it-works">
                        <h2
                            id="how-it-works"
                            className="text-base leading-4 font-semibold tracking-[-0.4px]"
                        >
                            How it works
                        </h2>
                        <ol className="grid gap-3 pt-3 md:grid-cols-3">
                            {steps.map((step, index) => (
                                <li key={step.title}>
                                    <GettingStartedStep
                                        item={step}
                                        number={index + 1}
                                        featured={index === 0}
                                    />
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section className="pt-6" aria-labelledby="why-sponsor">
                        <h2
                            id="why-sponsor"
                            className="text-base leading-4 font-semibold tracking-[-0.4px]"
                        >
                            Why sponsor with Health Bubba
                        </h2>
                        <div className="grid gap-3 pt-3 md:grid-cols-2">
                            {benefits.map((benefit) => (
                                <SponsorBenefitCard
                                    key={benefit.title}
                                    item={benefit}
                                />
                            ))}
                        </div>
                    </section>

                    <Card className="mt-6 border border-success/30 bg-success-muted/40 shadow-sm">
                        <CardContent className="flex flex-col items-start justify-between gap-4 p-6 sm:flex-row sm:items-center">
                            <div>
                                <h2 className="text-base leading-6 font-semibold">
                                    Ready to get your family covered?
                                </h2>
                                <p className="text-sm leading-5 text-muted-foreground">
                                    It takes about a minute. You can add
                                    beneficiaries right after.
                                </p>
                            </div>
                            <Link
                                href={home()}
                                className={cn(
                                    buttonVariants({ size: 'sm' }),
                                    'shrink-0',
                                )}
                            >
                                Choose your plan
                                <img
                                    src="/images/sponsor/empty-arrow.svg"
                                    alt=""
                                    className="size-4"
                                />
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </PortalShell>
        </>
    );
}
