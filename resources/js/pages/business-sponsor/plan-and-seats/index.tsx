import { Head } from '@inertiajs/react';
import { MinusIcon, PlusIcon } from 'lucide-react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { PlanBillingPageProps } from '@/types';

import { PlanCard } from '../../billing/partials/plan-cards';
import { SeatConfirmationDialog } from './partials/seat-confirmation-dialog';

const proRatedSeatPrice = 1050;

export default function BusinessPlanAndSeatsPage({
    accountTypeLabel,
    plans,
    subscription,
}: PlanBillingPageProps) {
    const [seatsToAdd, setSeatsToAdd] = useState(1);
    const [currentSeats, setCurrentSeats] = useState(6);
    const [confirmationOpen, setConfirmationOpen] = useState(false);
    const [announcement, setAnnouncement] = useState('');

    function confirmSeats() {
        setCurrentSeats((seats) => seats + seatsToAdd);
        setAnnouncement(
            `${seatsToAdd} ${seatsToAdd === 1 ? 'seat has' : 'seats have'} been added.`,
        );
        setConfirmationOpen(false);
        setSeatsToAdd(1);
    }

    return (
        <>
            <Head title="Plan & Billing" />
            <BusinessPortalShell>
                <div className="mx-auto w-full max-w-6xl pb-10">
                    <PageHeader
                        title="Plan & Billing"
                        description={`Manage the subscription, capacity, and billing cycle for your ${accountTypeLabel.toLowerCase()} account.`}
                    />

                    <Card className="mt-6">
                        <CardContent className="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:items-center">
                            {subscription?.plan ? (
                                <>
                                    <div>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h2 className="font-semibold">
                                                {subscription.plan.name}
                                            </h2>
                                            <span className="rounded-full bg-success/10 px-2.5 py-1 text-xs font-medium text-success">
                                                {subscription.statusLabel}
                                            </span>
                                        </div>
                                        <p className="pt-1 text-sm text-muted-foreground">
                                            {currentSeats} seats
                                            {subscription.endsAt
                                                ? ` · current term ends ${new Date(subscription.endsAt).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })}`
                                                : ' · ongoing term'}
                                        </p>
                                    </div>
                                    <div className="sm:text-right">
                                        <p className="text-2xl font-semibold">
                                            ₦
                                            {(
                                                Number(
                                                    subscription.plan.price,
                                                ) * currentSeats
                                            ).toLocaleString('en-NG')}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            per month for {currentSeats} seats
                                        </p>
                                    </div>
                                </>
                            ) : (
                                <div className="grid gap-1 sm:col-span-2">
                                    <h2 className="font-semibold">
                                        No subscription yet
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Select an available business plan to get
                                        started.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="mt-7">
                        <CardHeader className="gap-1 px-6 pt-6 pb-3">
                            <h2 className="text-base font-semibold">
                                Seat management
                            </h2>
                            <p className="text-sm leading-5 text-muted-foreground">
                                Add seats mid-cycle. You're charged a pro-rated
                                amount up to your renewal date, and the full
                                monthly allocation is injected immediately.
                            </p>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-end gap-6 px-6 pt-3 pb-6">
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Seats to add
                                </span>
                                <div className="flex items-center gap-4">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        aria-label="Remove one seat"
                                        disabled={seatsToAdd === 1}
                                        onClick={() =>
                                            setSeatsToAdd((seats) =>
                                                Math.max(1, seats - 1),
                                            )
                                        }
                                    >
                                        <MinusIcon className="size-4" />
                                    </Button>
                                    <output
                                        aria-live="polite"
                                        className="min-w-6 text-center text-lg font-semibold"
                                    >
                                        {seatsToAdd}
                                    </output>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        aria-label="Add one seat"
                                        onClick={() =>
                                            setSeatsToAdd((seats) => seats + 1)
                                        }
                                    >
                                        <PlusIcon className="size-4" />
                                    </Button>
                                </div>
                            </div>
                            <div className="grid gap-0.5">
                                <span className="text-sm text-muted-foreground">
                                    Pro-rated charge now
                                </span>
                                <output className="text-xl font-semibold">
                                    ₦
                                    {(
                                        seatsToAdd * proRatedSeatPrice
                                    ).toLocaleString()}
                                </output>
                            </div>
                            <Button
                                className="mb-0"
                                onClick={() => setConfirmationOpen(true)}
                            >
                                Add {seatsToAdd}{' '}
                                {seatsToAdd === 1 ? 'seat' : 'seats'}
                            </Button>
                        </CardContent>
                    </Card>

                    <section className="pt-12" aria-labelledby="plans-heading">
                        <h2
                            id="plans-heading"
                            className="pb-3 text-lg font-semibold"
                        >
                            Available plans
                        </h2>
                        <div className="grid max-w-4xl items-stretch gap-5 md:grid-cols-2">
                            {plans.map((plan) => (
                                <PlanCard
                                    key={plan.id}
                                    plan={plan}
                                    onSelect={() =>
                                        setAnnouncement(
                                            `${plan.name} selected.`,
                                        )
                                    }
                                />
                            ))}
                        </div>
                    </section>

                    <SeatConfirmationDialog
                        open={confirmationOpen}
                        seats={seatsToAdd}
                        onOpenChange={setConfirmationOpen}
                        onConfirm={confirmSeats}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
        </>
    );
}
