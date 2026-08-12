import { Head, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import type { MedicalAccessPageProps } from '@/types';

import { AccessRequestsTable } from './partials/access-requests-table';
import { RequestAccessDialog } from './partials/request-access-dialog';

export default function MedicalAccessIndex({
    requests,
    beneficiaries,
    dataTypes,
}: MedicalAccessPageProps) {
    const { flash } = usePage().props;

    return (
        <>
            <Head title="Medical Access" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Medical Access"
                        description="Request consent-gated access to beneficiary clinical data."
                        action={
                            <RequestAccessDialog
                                beneficiaries={beneficiaries}
                                dataTypes={dataTypes}
                            />
                        }
                    />

                    {flash.success && (
                        <p
                            className="mt-5 rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success"
                            role="status"
                        >
                            {flash.success}
                        </p>
                    )}

                    {beneficiaries.length === 0 && (
                        <p className="mt-5 rounded-xl border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                            Medical access requests require an active
                            beneficiary linked to a HealthBubba account.
                        </p>
                    )}

                    <section
                        className="pt-6"
                        aria-label="Medical access requests"
                    >
                        <AccessRequestsTable requests={requests} />
                    </section>
                </div>
            </PortalShell>
        </>
    );
}
