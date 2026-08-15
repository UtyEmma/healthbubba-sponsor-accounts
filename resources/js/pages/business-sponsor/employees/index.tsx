import { Head, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import type { WorkspaceBeneficiaryPageProps } from '@/types';
import { AddEmployeeDialog } from './partials/add-employee-dialog';
import { EmployeesTable } from './partials/employees-table';

export default function EmployeesPage({
    invitations,
    capacity,
    counts,
    importResult,
}: WorkspaceBeneficiaryPageProps) {
    const { flash, workspacePermissions } = usePage().props;

    return (
        <>
            <Head title="Employees" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Employees"
                        description="Provision and manage healthcare seats for your workforce."
                        action={
                            workspacePermissions.canManage ? (
                                <AddEmployeeDialog capacity={capacity} />
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
                        aria-label="Employee seat overview"
                    >
                        <SummaryCard
                            label="Active employees"
                            value={String(counts.active)}
                        />
                        <SummaryCard
                            label="Pending invitations"
                            value={String(counts.pending)}
                        />
                        <SummaryCard
                            label="Seats available"
                            value={`${capacity.remaining} of ${capacity.total}`}
                        />
                    </section>

                    {capacity.unavailableReason && (
                        <p className="pt-4 text-sm text-muted-foreground">
                            {capacity.unavailableReason}
                        </p>
                    )}

                    {importResult && (
                        <section
                            className="mt-5 rounded-2xl border bg-card p-5"
                            aria-label="Import results"
                        >
                            <h2 className="font-semibold">
                                Import complete: {importResult.imported}{' '}
                                invited, {importResult.skipped} skipped
                            </h2>
                            {importResult.errors.length > 0 && (
                                <ul className="mt-3 max-h-44 space-y-2 overflow-y-auto text-sm text-destructive">
                                    {importResult.errors.map((error) => (
                                        <li
                                            key={`${error.row}-${error.errors.join('-')}`}
                                        >
                                            Row {error.row}:{' '}
                                            {error.errors.join(' ')}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    )}

                    <EmployeesTable
                        invitations={invitations}
                        canManage={workspacePermissions.canManage}
                    />
                </div>
            </DashboardLayout>
        </>
    );
}

function SummaryCard({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="p-5">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="text-2xl font-semibold">{value}</p>
            </CardContent>
        </Card>
    );
}
