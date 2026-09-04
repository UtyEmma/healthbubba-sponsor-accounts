import { Head, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { PaymentStatusNotice } from '@/components/payment-status-notice';
import { Disclose } from '@/components/toggle/disclose';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PlanBillingPageProps } from '@/types';
import { CapacityPurchaseCard } from './partials/capacity-purchase-card';
import { PlanCard } from './partials/plan-cards';
import { PlanChangeDialog } from './partials/plan-change-dialog';
import { PlanCheckoutDialog } from './partials/plan-checkout-dialog';
import { PlanFaq } from './partials/plan-faq';
import { SubscriptionRenewalDialog } from './partials/subscription-renewal-dialog';
import { DashboardLayout } from '@/layouts/dashboard';

const nairaFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
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

function formatDate(value: string | null): string {
    return value ? format(new Date(value), 'do MMM') : 'Not scheduled';
}

export default function ({
    accountType,
    plans,
    subscription,
    wallet,
    capacityPurchase,
}: PlanBillingPageProps) {
    const { errors, flash } = usePage().props;
    const [selectedPlan, setSelectedPlan] = useState<
        PlanBillingPageProps['plans'][number] | null
    >(null);
    const [renewalOpen, setRenewalOpen] = useState(false);

    const renewalDate =
        subscription?.status === 'trialing'
            ? subscription.trialEndsAt
            : subscription?.endsAt;

    return (
        <>
            <Head title="Plan & Billing" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl pb-10">
                    <PageHeader
                        title="Plan & Billing"
                        description={`Manage the plans and subscription attached to your account.`}
                    />

                    <PaymentStatusNotice
                        success={flash.success}
                        error={
                            errors.payment ??
                            errors.capacity ??
                            errors.plan_change
                        }
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
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Disclose
                                                as="p"
                                                show={capacityPurchase}
                                            >
                                                {`${capacityPurchase?.current_capacity} ${capacityPurchase?.unit === 'seat' ? 'seats' : 'beneficiaries'}`}
                                            </Disclose>
                                            &bull;
                                            <p>
                                                {subscription.status ===
                                                'trialing'
                                                    ? 'Trial ends'
                                                    : 'Renews'}{' '}
                                                {formatDate(
                                                    renewalDate ?? null,
                                                )}
                                            </p>
                                        </div>
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
                                                : 'Renews'}
                                        </dt>
                                        <dd className="font-medium">
                                            {formatDate(renewalDate ?? null)}
                                        </dd>
                                    </dl>
                                    <div className="lg:text-right">
                                        <p className="text-2xl font-semibold">
                                            {nairaFormatter.format(
                                                Number(
                                                    subscription.renewalAmount,
                                                ),
                                            )}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {subscription.scheduledPlan
                                                ?.billingLabel ??
                                                subscription.plan.billingLabel}
                                        </p>
                                    </div>
                                    {subscription.renewalPaymentAvailable && (
                                        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4 lg:col-span-4">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    Subscription payment due
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Pay from Wallet or use
                                                    Paystack to keep your plan
                                                    active.
                                                </p>
                                            </div>
                                            <Button
                                                type="button"
                                                size="compact"
                                                onClick={() =>
                                                    setRenewalOpen(true)
                                                }
                                            >
                                                Renew plan
                                            </Button>
                                        </div>
                                    )}
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

                    {capacityPurchase && (
                        <CapacityPurchaseCard summary={capacityPurchase} />
                    )}

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
                                    Showing plans for your {accountType}{' '}
                                    workspace only.
                                </p>
                            </div>
                        </div>

                        {plans.length > 0 ? (
                            <div className="grid items-stretch gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {plans.map((plan) => (
                                    <PlanCard
                                        key={plan.id}
                                        plan={plan}
                                        onSelect={setSelectedPlan}
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
                    <PlanCheckoutDialog
                        accountType={accountType}
                        wallet={wallet}
                        plan={selectedPlan}
                        open={
                            selectedPlan !== null &&
                            selectedPlan.plan_change === null &&
                            !selectedPlan.is_current
                        }
                        onOpenChange={(open) => {
                            if (!open) {
                                setSelectedPlan(null);
                            }
                        }}
                    />
                    {subscription && (
                        <PlanChangeDialog
                            subscriptionId={subscription.id}
                            currentPlanName={
                                subscription.plan?.name ?? 'Current plan'
                            }
                            currentCapacity={subscription.capacityCount}
                            wallet={wallet}
                            plan={selectedPlan}
                            open={selectedPlan?.plan_change?.available === true}
                            onOpenChange={(open) => {
                                if (!open) {
                                    setSelectedPlan(null);
                                }
                            }}
                        />
                    )}
                    {subscription && (
                        <SubscriptionRenewalDialog
                            subscription={subscription}
                            wallet={wallet}
                            open={renewalOpen}
                            onOpenChange={setRenewalOpen}
                        />
                    )}
                </div>
            </DashboardLayout>
        </>
    );
}
