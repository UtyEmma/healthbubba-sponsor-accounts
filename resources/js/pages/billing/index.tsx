import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { AccountType, PlanBillingPageProps } from '@/types';

import { institutionalNavigation } from '../institutional-sponsor/partials/institutional-navigation';
import { PlanCard } from './partials/plan-cards';
import { PlanFaq } from './partials/plan-faq';
import { PlanSuccessDialog } from './partials/plan-success-dialog';

const nairaFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const statusTone: Record<
    NonNullable<PlanBillingPageProps['subscription']>['status'],
    string
> = {
    active: 'bg-success/10 text-success',
    trialing: 'bg-information/10 text-information',
    past_due: 'bg-warning/10 text-warning',
    cancelled: 'bg-destructive/10 text-destructive',
    expired: 'bg-muted text-muted-foreground',
};

function BillingShell({
    accountType,
    children,
}: {
    accountType: AccountType;
    children: ReactNode;
}) {
    if (accountType === 'individual') {
        return <PortalShell>{children}</PortalShell>;
    }

    return (
        <BusinessPortalShell
            navigation={
                accountType === 'institution'
                    ? institutionalNavigation
                    : undefined
            }
            navigationLabel={
                accountType === 'institution'
                    ? 'Institutional sponsor navigation'
                    : undefined
            }
        >
            {children}
        </BusinessPortalShell>
    );
}

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : 'Not scheduled';
}

export default function ({
    plans,
    subscription,
}: PlanBillingPageProps) {
    const {workspace} = usePage().props

    const renewalDate =
        subscription?.status === 'trialing'
            ? subscription.trialEndsAt
            : subscription?.endsAt;

    return (
        <>
            <Head title="Plan & Billing" />
            <BillingShell accountType={workspace.type}>
                <div className="mx-auto w-full max-w-6xl pb-10">
                    <PageHeader
                        title="Plan & Billing"
                        description={`Manage the plans and subscription attached to your account.`}
                    />

                    <Card className="mt-6">
                        <CardContent className="grid gap-6 px-5 py-5 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))] lg:items-center">
                            {subscription?.plan ? (
                                <>
                                    <div className="grid gap-1">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h2 className="font-semibold">
                                                {subscription.plan.name}
                                            </h2>
                                            <span
                                                className={cn(
                                                    'rounded-full px-2.5 py-1 text-xs font-medium',
                                                    statusTone[
                                                        subscription.status
                                                    ],
                                                )}
                                            >
                                                {subscription.statusLabel}
                                            </span>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {subscription.isValid
                                                ? 'Your subscription currently provides plan access.'
                                                : 'This subscription is not currently providing plan access.'}
                                        </p>
                                    </div>
                                    <dl className="grid gap-1 text-sm">
                                        <dt className="text-muted-foreground">
                                            Started
                                        </dt>
                                        <dd className="font-medium">
                                            {formatDate(subscription.startsAt)}
                                        </dd>
                                    </dl>
                                    <dl className="grid gap-1 text-sm">
                                        <dt className="text-muted-foreground">
                                            {subscription.status === 'trialing'
                                                ? 'Trial ends'
                                                : 'Current term ends'}
                                        </dt>
                                        <dd className="font-medium">
                                            {formatDate(renewalDate ?? null)}
                                        </dd>
                                    </dl>
                                    <div className="lg:text-right">
                                        <p className="text-2xl font-semibold">
                                            {nairaFormatter.format(
                                                Number(subscription.plan.price),
                                            )}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {subscription.plan.billingLabel}
                                        </p>
                                    </div>
                                </>
                            ) : (
                                <div className="grid gap-1 lg:col-span-4">
                                    <h2 className="font-semibold">
                                        No subscription yet
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Select one of the plans available for
                                        your account type to get started.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <section className="pt-8" aria-labelledby="plans-heading">
                        <div className="flex flex-wrap items-end justify-between gap-3 pb-4">
                            <div>
                                <h2
                                    id="plans-heading"
                                    className="text-lg font-semibold"
                                >
                                    Available plans
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Showing plans for only.
                                </p>
                            </div>
                        </div>

                        {plans.length > 0 ? (
                            <div className="grid items-stretch gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {plans.map((plan) => (
                                    <PlanCard
                                        key={plan.id}
                                        plan={plan}
                                    />
                                ))}
                            </div>
                        ) : (
                            <Card>
                                <CardContent className="px-6 py-10 text-center text-sm text-muted-foreground">
                                    No active plans are currently available for
                                    this account type.
                                </CardContent>
                            </Card>
                        )}
                    </section>

                    <PlanFaq />
                    {/* <PlanSuccessDialog
                        open={selectedPlanName !== null}
                        onOpenChange={(open) => {
                            if (!open) {
                                setSelectedPlanName(null);
                            }
                        }}
                        planName={selectedPlanName}
                    /> */}
                </div>
            </BillingShell>
        </>
    );
}
