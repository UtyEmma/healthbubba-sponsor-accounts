import { Head, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { Card, CardContent } from '@/components/ui/card';
import type { WorkspaceBeneficiaryPageProps } from '@/types';
import { AddBeneficiaryDialog } from './partials/add-beneficiary-dialog';
import { BeneficiariesTable } from './partials/beneficiaries-table';

export default function BeneficiariesIndex({
    invitations,
    capacity,
    counts,
}: WorkspaceBeneficiaryPageProps) {
    const { flash } = usePage().props;

    return (
        <>
            <Head title="Beneficiaries" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Beneficiaries"
                        description="Invite and manage the people covered by your sponsorship."
                        action={<AddBeneficiaryDialog capacity={capacity} />}
                    />

                    {flash.success && (
                        <p className="mt-5 rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                            {flash.success}
                        </p>
                    )}

                    <section
                        className="grid gap-5 pt-6 sm:grid-cols-3"
                        aria-label="Beneficiary overview"
                    >
                        <SummaryCard
                            label="Active"
                            value={String(counts.active)}
                        />
                        <SummaryCard
                            label="Pending invites"
                            value={String(counts.pending)}
                        />
                        <SummaryCard
                            label="Capacity"
                            value={`${capacity.used}/${capacity.total}`}
                        />
                    </section>

                    {capacity.unavailableReason && (
                        <p className="pt-4 text-sm text-muted-foreground">
                            {capacity.unavailableReason}
                        </p>
                    )}

                    <section className="pt-5" aria-label="Beneficiary list">
                        <BeneficiariesTable invitations={invitations} />
                    </section>
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
