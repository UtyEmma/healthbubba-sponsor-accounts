import { Head, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import type { WorkspaceBeneficiaryPageProps } from '@/types';
import { AddBeneficiaryDialog } from './partials/add-beneficiary-dialog';
import { BeneficiariesTable } from './partials/beneficiaries-table';

export default function BeneficiariesIndex({
    invitations,
    capacity,
    counts,
}: WorkspaceBeneficiaryPageProps) {
    const { flash, workspacePermissions } = usePage().props;

    return (
        <>
            <Head title="Covered people" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Covered people"
                        description={
                            capacity.unavailableReason
                                ? 'Activate a plan to manage sponsor and beneficiary coverage.'
                                : capacity.sponsorCoveredByDefault
                                  ? 'Your coverage includes you as the primary sponsor, plus the dependants on your plan.'
                                  : 'Your current plan covers the beneficiaries you add; the primary sponsor is not included automatically.'
                        }
                        action={
                            workspacePermissions.canManage ? (
                                <AddBeneficiaryDialog />
                            ) : undefined
                        }
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
                            label={
                                capacity.sponsorCoveredByDefault
                                    ? 'Active covered people'
                                    : 'Active beneficiaries'
                            }
                            value={String(counts.active)}
                        />
                        <SummaryCard
                            label="Pending beneficiaries"
                            value={String(counts.pending)}
                        />
                        <SummaryCard
                            label="Beneficiary capacity"
                            value={`${capacity.used}/${capacity.total}`}
                        />
                    </section>

                    {capacity.unavailableReason && (
                        <p className="pt-4 text-sm text-muted-foreground">
                            {capacity.unavailableReason}
                        </p>
                    )}

                    <section className="pt-5" aria-label="Covered people list">
                        <BeneficiariesTable
                            invitations={invitations}
                            canManage={workspacePermissions.canManage}
                        />
                    </section>
                </div>
            </DashboardLayout>
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
