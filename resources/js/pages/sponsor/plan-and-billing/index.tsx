import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { Card, CardContent } from '@/components/ui/card';

import { PlanCard } from './partials/plan-cards';
import type { Plan } from './partials/plan-cards';
import { PlanFaq } from './partials/plan-faq';
import { PlanSuccessDialog } from './partials/plan-success-dialog';

const sharedFeatures = [
    { label: 'On-Demand (Consult Now)', included: true, help: true },
    { label: 'Scheduled Appointments', included: true, help: true },
];

const plans: Plan[] = [
    {
        name: 'Basic Plan',
        audience: 'Small families',
        price: '₦20,000',
        details: [
            ['Beneficiaries', '3 included, up to 6'],
            ['Extra beneficiary', '₦7,000/mo each'],
            ['GP consultations', '5 / month, shared'],
            ['Specialist', '2 / month, shared'],
        ],
        features: [
            ...sharedFeatures,
            { label: 'Add Up to 6 Beneficiaries', included: true, help: true },
            { label: 'Follow-Up Tracking', included: false, help: true },
            { label: 'Priority Support', included: false, help: true },
            { label: 'Dedicated Coordinator', included: false, help: true },
            {
                label: 'Chronic Disease Monitoring',
                included: false,
                help: true,
            },
        ],
        action: 'Downgrade',
    },
    {
        name: 'Premium Plan',
        audience: 'Large families',
        price: '₦33,000',
        details: [
            ['Beneficiaries', '6 included, up to 12'],
            ['Extra beneficiary', '₦7,000/mo each'],
            ['GP consultations', '10 / month, shared'],
            ['Specialist', '3 / month, shared'],
        ],
        features: [
            ...sharedFeatures,
            { label: 'Add Up to 12 Beneficiaries', included: true, help: true },
            { label: 'Follow-Up Tracking', included: true, help: true },
            { label: 'Priority Support', included: true, help: true },
            { label: 'Dedicated Coordinator', included: false, help: true },
            {
                label: 'Chronic Disease Monitoring',
                included: false,
                help: true,
            },
        ],
        action: 'Current plan',
    },
    {
        name: 'Coordinated Care Plan',
        audience: 'Elderly & chronic disease patients',
        price: '₦59,000',
        details: [
            ['Beneficiaries', '2 (fixed)'],
            ['GP consultations', '12 / month, shared'],
            ['Specialist', '4 / month, shared'],
        ],
        features: [
            ...sharedFeatures,
            { label: 'Add Extra Beneficiaries', included: false, help: true },
            { label: 'Follow-Up Tracking', included: true, help: true },
            { label: 'Priority Support', included: true, help: true },
            { label: 'Dedicated Coordinator', included: true, help: true },
            { label: 'Chronic Disease Monitoring', included: true, help: true },
        ],
        action: 'Upgrade',
    },
];

export default function PlanAndBillingIndex() {
    const [successOpen, setSuccessOpen] = useState(false);

    return (
        <>
            <Head title="Plan & Billing" />
            <PortalShell>
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
                                        Premium Plan
                                    </h2>
                                    <span className="text-xs font-medium text-success">
                                        Active
                                    </span>
                                </div>
                                <p className="pt-1 text-sm text-muted-foreground">
                                    4 of 12 beneficiaries · renews 30 June
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-2xl font-semibold">
                                    ₦33,000
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    per month
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <section className="pt-7" aria-labelledby="plans-heading">
                        <h2
                            id="plans-heading"
                            className="pb-3 text-lg font-semibold"
                        >
                            Available plans
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {plans.map((plan) => (
                                <PlanCard
                                    key={plan.name}
                                    plan={plan}
                                    onUpgrade={() => setSuccessOpen(true)}
                                />
                            ))}
                        </div>
                    </section>

                    <PlanFaq />
                    <PlanSuccessDialog
                        open={successOpen}
                        onOpenChange={setSuccessOpen}
                    />
                </div>
            </PortalShell>
        </>
    );
}
