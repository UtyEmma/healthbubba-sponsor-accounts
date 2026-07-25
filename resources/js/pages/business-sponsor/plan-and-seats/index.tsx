import { Head } from '@inertiajs/react';
import {
    CheckIcon,
    CircleHelpIcon,
    MinusIcon,
    PlusIcon,
    XIcon,
} from 'lucide-react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

import { SeatConfirmationDialog } from './partials/seat-confirmation-dialog';

type BusinessPlan = {
    name: string;
    audience: string;
    price: string;
    details: Array<[string, string]>;
    features: Array<{ label: string; included: boolean; help?: boolean }>;
    action: 'Downgrade' | 'Current plan';
};

const plans: BusinessPlan[] = [
    {
        name: 'Business Basic',
        audience: 'SMEs & corporate teams',
        price: '₦5,000',
        details: [
            ['GP consultations', '2 per employee / month'],
            ['Specialist', 'Not included'],
        ],
        features: [
            {
                label: '2 GP Consultations / employee',
                included: true,
                help: true,
            },
            { label: 'Specialist Consultations', included: false, help: true },
            { label: 'GP Consult Now / Scheduled', included: true, help: true },
            {
                label: 'Bulk HR Upload & List Export',
                included: true,
                help: true,
            },
            { label: 'Activity & Coverage Logs', included: true, help: true },
            {
                label: 'Lab Test & Medication Discounts',
                included: false,
                help: true,
            },
            { label: 'Enhanced Analytics Suite', included: false, help: true },
        ],
        action: 'Downgrade',
    },
    {
        name: 'Business Premium',
        audience: 'Enterprises & logistics companies',
        price: '₦10,500',
        details: [
            ['GP consultations', '3 per employee / month'],
            ['Specialist', '1 per employee / month'],
        ],
        features: [
            {
                label: '3 GP Consultations / employee',
                included: true,
                help: true,
            },
            {
                label: '1 Specialist Consultation / employee',
                included: true,
                help: true,
            },
            {
                label: 'GP & Specialist Consult Now / Scheduled',
                included: true,
                help: true,
            },
            {
                label: 'Bulk HR Upload & List Export',
                included: true,
                help: true,
            },
            { label: 'Activity & Coverage Logs', included: true, help: true },
            { label: 'Priority Customer Support', included: true, help: true },
            {
                label: 'Lab Test & Medication Discounts',
                included: true,
                help: true,
            },
            { label: 'Enhanced Analytics Suite', included: true, help: true },
        ],
        action: 'Current plan',
    },
];

const proRatedSeatPrice = 1050;

export default function BusinessPlanAndSeatsPage() {
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
                        description="Manage your subscription, capacity, and billing cycle."
                    />

                    <Card className="mt-6">
                        <CardContent className="flex min-h-[89px] items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <div className="flex items-center gap-5">
                                    <h2 className="font-semibold">
                                        Business Premium
                                    </h2>
                                    <span className="text-xs font-medium text-success">
                                        Active
                                    </span>
                                </div>
                                <p className="pt-1 text-sm text-muted-foreground">
                                    {currentSeats} seats · renews 3 July
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-2xl font-semibold">
                                    ₦63,000
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    per month
                                </p>
                            </div>
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
                        <div className="grid max-w-[764px] gap-4 md:grid-cols-2">
                            {plans.map((plan) => (
                                <BusinessPlanCard
                                    key={plan.name}
                                    plan={plan}
                                    onDowngrade={() =>
                                        setAnnouncement(
                                            'Business Basic downgrade selected.',
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

function BusinessPlanCard({
    plan,
    onDowngrade,
}: {
    plan: BusinessPlan;
    onDowngrade: () => void;
}) {
    const isCurrent = plan.action === 'Current plan';

    return (
        <Card
            className={cn(
                'flex min-h-[563px] flex-col',
                isCurrent && 'border-success',
            )}
        >
            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                <h3 className="text-base font-semibold">{plan.name}</h3>
                <p className="text-sm text-muted-foreground">{plan.audience}</p>
                <p className="pt-3 text-3xl leading-9 font-semibold tracking-[-0.7px]">
                    {plan.price}
                    <span className="text-sm font-normal text-muted-foreground">
                        /mo per seat
                    </span>
                </p>
            </CardHeader>
            <CardContent className="flex-1 px-6 pt-3">
                <dl className="grid gap-2 border-b pb-4 text-sm">
                    {plan.details.map(([label, value]) => (
                        <div key={label} className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">{label}</dt>
                            <dd className="text-right font-medium">{value}</dd>
                        </div>
                    ))}
                </dl>
                <ul className="grid gap-2 pt-4 text-sm">
                    {plan.features.map((feature) => (
                        <li
                            key={feature.label}
                            className={cn(
                                'flex items-center gap-2',
                                !feature.included && 'text-muted-foreground',
                            )}
                        >
                            {feature.included ? (
                                <CheckIcon className="size-4 shrink-0 text-success" />
                            ) : (
                                <XIcon className="size-4 shrink-0 text-muted-foreground/60" />
                            )}
                            <span>{feature.label}</span>
                            {feature.help && (
                                <CircleHelpIcon
                                    className="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            )}
                        </li>
                    ))}
                </ul>
            </CardContent>
            <CardFooter className="px-6 pb-6">
                <Button
                    className="w-full"
                    disabled={isCurrent}
                    variant={isCurrent ? 'muted' : 'outline'}
                    onClick={isCurrent ? undefined : onDowngrade}
                >
                    {plan.action}
                </Button>
            </CardFooter>
        </Card>
    );
}
