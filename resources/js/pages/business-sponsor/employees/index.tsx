import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';

import { AddEmployeeDialog } from './partials/add-employee-dialog';
import { EmployeesTable, type Employee } from './partials/employees-table';

const employees: Employee[] = [
    {
        id: 1,
        name: 'David Smith',
        role: 'Fleet Manager',
        employeeId: 'SL-001',
        department: 'Operations',
        status: 'Active',
        seatUsage: 'GP 1/3 · Spec 0/1',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 2,
        name: 'David Smith',
        role: 'Fleet Manager',
        employeeId: 'SL-001',
        department: 'Operations',
        status: 'Active',
        seatUsage: 'GP 1/3 · Spec 0/1',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 3,
        name: 'David Smith',
        role: 'Fleet Manager',
        employeeId: 'SL-001',
        department: 'Operations',
        status: 'Pending',
        seatUsage: 'GP 1/3 · Spec 0/1',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 4,
        name: 'David Smith',
        role: 'Fleet Manager',
        employeeId: 'SL-001',
        department: 'Operations',
        status: 'Active',
        seatUsage: 'GP 1/3 · Spec 0/1',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
    {
        id: 5,
        name: 'David Smith',
        role: 'Fleet Manager',
        employeeId: 'SL-001',
        department: 'Operations',
        status: 'Suspended',
        seatUsage: 'GP 1/3 · Spec 0/1',
        avatar: '/images/sponsor/beneficiary-david.png',
    },
];

export default function EmployeesPage() {
    const [announcement, setAnnouncement] = useState('');

    return (
        <>
            <Head title="Employees" />
            <BusinessPortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Employees"
                        description="Provision and manage healthcare seats for your workforce."
                        action={
                            <AddEmployeeDialog
                                onContinue={(method) =>
                                    setAnnouncement(
                                        method === 'csv'
                                            ? 'CSV upload selected.'
                                            : 'Manual employee entry selected.',
                                    )
                                }
                            />
                        }
                    />
                    <EmployeesTable
                        employees={employees}
                        onAction={setAnnouncement}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
        </>
    );
}
