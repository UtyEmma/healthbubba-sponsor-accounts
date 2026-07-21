import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';

import { AccessRecordsDialog } from './partials/access-records-dialog';
import { AccessRequestsTable } from './partials/access-requests-table';
import type { AccessRequest } from './partials/access-requests-table';
import { RequestAccessDialog } from './partials/request-access-dialog';
import type { MedicalAccessFormData } from './partials/request-access-dialog';

const initialRequests: AccessRequest[] = [
    {
        id: 1,
        beneficiary: 'Ngozi Okafor',
        dataType: 'Clinical diagnosis & case notes',
        requested: '18 Jun, 2026',
        expires: '18 Jul, 2026',
        status: 'Active',
    },
    {
        id: 2,
        beneficiary: 'Chidi Okafor',
        dataType: 'Prescription records',
        requested: '18 Jun, 2026',
        expires: '18 Jul, 2026',
        status: 'Pending',
    },
    {
        id: 3,
        beneficiary: 'Jane Okafor',
        dataType: 'Prescription records',
        requested: '18 Jun, 2026',
        expires: '18 Jul, 2026',
        status: 'Expired',
    },
    {
        id: 4,
        beneficiary: 'Ngozi Okafor',
        dataType: 'Prescription records',
        requested: '18 Jun, 2026',
        expires: '18 Jul, 2026',
        status: 'Active',
    },
];

export default function MedicalAccessIndex() {
    const [requests, setRequests] = useState(initialRequests);
    const [selectedRequest, setSelectedRequest] =
        useState<AccessRequest | null>(null);
    const [announcement, setAnnouncement] = useState('');

    function addRequest(data: MedicalAccessFormData) {
        const nextId = Math.max(...requests.map(({ id }) => id)) + 1;
        setRequests((current) => [
            ...current,
            {
                id: nextId,
                beneficiary: data.beneficiary,
                dataType: data.dataType,
                requested: '21 Jul, 2026',
                expires: '20 Aug, 2026',
                status: 'Pending',
            },
        ]);
        setAnnouncement(`Medical access requested from ${data.beneficiary}.`);
    }

    return (
        <>
            <Head title="Medical Access" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Medical Access"
                        description="Request consent-gated access to beneficiary clinical data."
                        action={<RequestAccessDialog onSubmit={addRequest} />}
                    />

                    <section
                        className="pt-6"
                        aria-label="Medical access requests"
                    >
                        <AccessRequestsTable
                            requests={requests}
                            onView={setSelectedRequest}
                        />
                    </section>

                    <AccessRecordsDialog
                        request={selectedRequest}
                        onOpenChange={(open) => {
                            if (!open) {
                                setSelectedRequest(null);
                            }
                        }}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </PortalShell>
        </>
    );
}
