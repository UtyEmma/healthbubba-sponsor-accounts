import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { Card, CardContent } from '@/components/ui/card';
import { AddBeneficiaryDialog } from './partials/add-beneficiary-dialog';
import type { BeneficiaryFormData } from './partials/add-beneficiary-dialog';
import { BeneficiariesTable } from './partials/beneficiaries-table';
import type { Beneficiary } from './partials/beneficiaries-table';

const initialBeneficiaries: Beneficiary[] = [
    {
        id: 1,
        name: 'David Smith',
        email: 'chidi@example.com',
        phone: '+234 803 444 5566',
        status: 'Active',
        joined: '10/25/2025',
        allocations: '2 GP, 3 Specialist',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 2,
        name: 'Alexander Ogunyemi',
        email: 'alex@example.com',
        phone: '+234 803 444 5566',
        status: 'Active',
        joined: '10/25/2025',
        allocations: '1 GP, 0 Specialist',
        avatar: '/images/sponsor/beneficiary-alexander.png',
    },
    {
        id: 3,
        name: 'Dominic Barrow',
        email: 'dominic@example.com',
        phone: '+234 803 444 5566',
        status: 'Active',
        joined: '10/25/2025',
        allocations: '0 GP, 0 Specialist',
        avatar: '/images/sponsor/beneficiary-dominic.png',
    },
    {
        id: 4,
        name: 'David Smith',
        email: 'david@example.com',
        phone: '+234 803 444 5566',
        status: 'Pending',
        joined: '10/25/2025',
        allocations: '--',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 5,
        name: 'David Smith',
        email: 'smith@example.com',
        phone: '+234 803 444 5566',
        status: 'Inactive',
        joined: '10/25/2025',
        allocations: '--',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
];

export default function BeneficiariesIndex() {
    const [beneficiaries, setBeneficiaries] = useState(initialBeneficiaries);
    const [announcement, setAnnouncement] = useState('');

    function addBeneficiary(data: BeneficiaryFormData) {
        setBeneficiaries((current) => [
            ...current,
            {
                id: Math.max(...current.map(({ id }) => id)) + 1,
                name: `${data.firstName} ${data.lastName}`,
                email: data.email,
                phone: data.phone,
                status: 'Pending',
                joined: new Intl.DateTimeFormat('en-US').format(new Date()),
                allocations: '--',
                avatar: '/images/sponsor/beneficiary-david.png',
            },
        ]);
        setAnnouncement(
            `Invitation prepared for ${data.firstName} ${data.lastName}`,
        );
    }

    const activeCount = beneficiaries.filter(
        ({ status }) => status === 'Active',
    ).length;
    const pendingCount = beneficiaries.filter(
        ({ status }) => status === 'Pending',
    ).length;

    return (
        <>
            <Head title="Beneficiaries" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Beneficiaries"
                        description="Invite and manage the people covered by your sponsorship."
                        action={<AddBeneficiaryDialog onAdd={addBeneficiary} />}
                    />

                    <section
                        className="grid gap-5 pt-6 sm:grid-cols-3"
                        aria-label="Beneficiary overview"
                    >
                        <SummaryCard
                            label="Active"
                            value={String(activeCount)}
                        />
                        <SummaryCard
                            label="Pending invites"
                            value={String(pendingCount)}
                        />
                        <SummaryCard
                            label="Capacity"
                            value={`${activeCount + pendingCount}/12`}
                        />
                    </section>

                    <section
                        className="pt-[18px]"
                        aria-label="Beneficiary list"
                    >
                        <BeneficiariesTable
                            beneficiaries={beneficiaries}
                            onAction={setAnnouncement}
                        />
                    </section>
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </PortalShell>
        </>
    );
}

function SummaryCard({ label, value }: { label: string; value: string }) {
    return (
        <Card className="min-h-[92px]">
            <CardContent className="p-5">
                <p className="text-sm leading-5 text-muted-foreground">
                    {label}
                </p>
                <p className="text-2xl leading-8 font-semibold">{value}</p>
            </CardContent>
        </Card>
    );
}
