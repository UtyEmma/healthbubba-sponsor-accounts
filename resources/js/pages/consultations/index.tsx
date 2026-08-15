import { Head, Link, usePage } from '@inertiajs/react';
import { UserRoundPlusIcon, UsersRoundIcon } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { buttonVariants } from '@/components/ui/button';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import beneficiaries from '@/routes/beneficiaries';
import business from '@/routes/business';
import type { ConsultationPageProps } from '@/types';
import { AllocationFallbackCard } from './partials/allocation-fallback';
import { ConsultationHistory } from './partials/consultation-history';
import { ConsultationOverview } from './partials/consultation-overview';

export default function ConsultationsIndex({
    consultations,
    coverage,
}: ConsultationPageProps) {
    const { workspace, workspacePermissions } = usePage().props;
    const managementAction =
        workspacePermissions.canManage && workspace.type === 'business' ? (
            <Link
                href={business.employees()}
                className={cn(
                    buttonVariants({ size: 'compact' }),
                    'self-start sm:self-auto',
                )}
            >
                <UsersRoundIcon className="size-4" />
                Manage employees
            </Link>
        ) : workspace.type === 'individual' ? (
            <Link
                href={beneficiaries.index()}
                className={cn(
                    buttonVariants({ size: 'compact' }),
                    'self-start sm:self-auto',
                )}
            >
                <UserRoundPlusIcon className="size-4" />
                Manage beneficiaries
            </Link>
        ) : undefined;

    return (
        <DashboardLayout>
            <Head title="Consultations" />

            <div className="mx-auto w-full max-w-6xl py-2 sm:py-4">
                <PageHeader
                    title="Sponsored consultations"
                    description="Track appointments funded from your workspace's GP and Specialist consultation allocations."
                    action={managementAction}
                />

                <ConsultationOverview coverage={coverage} />
                {workspacePermissions.canManage && <AllocationFallbackCard />}
                <ConsultationHistory consultations={consultations} />
            </div>
        </DashboardLayout>
    );
}
