import { Head, Link, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import { index as plansIndex } from '@/routes/plans';
import {
    GettingStartedStep,
    SponsorBenefitCard,
} from './partials/getting-started-cards';
import type { GettingStartedItem } from './partials/getting-started-cards';

type EmptyStateContent = {
    pageDescription: string;
    heroTitle: string;
    heroDescription: string;
    steps: GettingStartedItem[];
    benefitsTitle: string;
    benefits: GettingStartedItem[];
    readyTitle: string;
    readyDescription: string;
};

const individualContent: EmptyStateContent = {
    pageDescription:
        "Let's get your family covered — it starts with choosing a plan.",
    heroTitle: 'Sponsor quality healthcare for the people you love',
    heroDescription:
        'Choose a plan and your family can start booking scheduled and instant consultations today — while every beneficiary keeps full control of their own records.',
    steps: [
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
    ],
    benefitsTitle: 'Why sponsor with Health Bubba',
    benefits: [
        {
            title: 'Care for the whole family',
            description:
                'Cover parents, children and loved ones under a single sponsorship.',
            icon: 'empty-benefit-family.svg',
        },
        {
            title: 'See a doctor in minutes',
            description:
                'Scheduled and instant video consultations — no queues, no clinics.',
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
    ],
    readyTitle: 'Ready to get your family covered?',
    readyDescription:
        'It takes about a minute. You can add beneficiaries right after.',
};

const businessContent: EmptyStateContent = {
    pageDescription:
        "Let's get your team covered — it starts with choosing a business plan.",
    heroTitle: 'Give your workforce dependable access to healthcare',
    heroDescription:
        'Choose a plan, add employee seats and give each team member access to scheduled and instant consultations while their medical records remain private.',
    steps: [
        {
            title: 'Choose a business plan',
            description: 'Select the per-employee coverage your team needs.',
            icon: 'empty-step-plan.svg',
        },
        {
            title: 'Add employees',
            description:
                'Invite employees individually or upload your HR list.',
            icon: 'empty-step-beneficiary.svg',
        },
        {
            title: 'Track coverage',
            description:
                'Monitor enrollment and consultation usage without clinical data.',
            icon: 'empty-step-care.svg',
        },
    ],
    benefitsTitle: 'Why cover your team with Health Bubba',
    benefits: [
        {
            title: 'Coverage for every employee',
            description:
                'Give each enrolled employee a dedicated monthly consultation allowance.',
            icon: 'empty-benefit-family.svg',
        },
        {
            title: 'Care without the wait',
            description:
                'Employees can access scheduled and instant video consultations wherever they are.',
            icon: 'empty-benefit-doctor.svg',
        },
        {
            title: 'Private by design',
            description:
                'You see coverage activity while employees retain control of their medical records.',
            icon: 'empty-benefit-privacy.svg',
        },
        {
            title: 'Simple workforce administration',
            description:
                'Manage employee seats, bulk uploads and coverage from one place.',
            icon: 'empty-benefit-payment.svg',
        },
    ],
    readyTitle: 'Ready to cover your team?',
    readyDescription:
        'Choose a plan now, then invite employees or upload your workforce list.',
};

export default function EmptyState() {
    const { auth, workspace, workspacePermissions } = usePage().props;
    const isBusiness = workspace.type === 'business';
    const content = isBusiness ? businessContent : individualContent;
    const userFirstName = auth.user.name.trim().split(/\s+/)[0] || 'there';
    const canChoosePlan = workspacePermissions.canViewFinancial;

    return (
        <>
            <Head title="Welcome" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title={`Welcome to Health Bubba, ${userFirstName} 👋`}
                        description={content.pageDescription}
                    />

                    <section className="mt-6 rounded-xl border border-border/70 bg-linear-to-b from-success/10 to-transparent p-px">
                        <div className="flex min-h-[171px] items-center p-6 sm:p-8">
                            <div className="w-full">
                                <h2 className="max-w-[900px] text-[30px] leading-[38px] font-semibold tracking-[-1.5px] text-foreground sm:text-4xl sm:leading-[42px] sm:tracking-[-2px]">
                                    {content.heroTitle}
                                </h2>
                                <p className="pt-2 text-sm leading-[21px] text-muted-foreground sm:text-base">
                                    {content.heroDescription}
                                </p>
                                {canChoosePlan ? (
                                    <Link
                                        href={plansIndex()}
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
                                ) : (
                                    <p className="pt-5 text-sm font-medium text-foreground">
                                        Ask a workspace owner or administrator
                                        to activate a plan.
                                    </p>
                                )}
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
                            {content.steps.map((step, index) => (
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
                            {content.benefitsTitle}
                        </h2>
                        <div className="grid gap-3 pt-3 md:grid-cols-2">
                            {content.benefits.map((benefit) => (
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
                                    {content.readyTitle}
                                </h2>
                                <p className="text-sm leading-5 text-muted-foreground">
                                    {canChoosePlan
                                        ? content.readyDescription
                                        : 'A workspace owner or administrator can choose and activate a plan.'}
                                </p>
                            </div>
                            {canChoosePlan && (
                                <Link
                                    href={plansIndex()}
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
                            )}
                        </CardContent>
                    </Card>
                </div>
            </DashboardLayout>
        </>
    );
}
