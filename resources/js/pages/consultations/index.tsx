import { Head, Link } from '@inertiajs/react';
import { UserRoundPlusIcon } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import beneficiaries from '@/routes/beneficiaries';
import { ConsultationHistory } from './partials/consultation-history';
import { AllocationFallbackCard, ConsultationOverview } from './partials/consultation-overview';

export default function ConsultationsIndex() {
    return (
        <>
            <Head title="Consultations" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Consultations"
                        description="Your shared consultation pool and how it scales with beneficiaries."
                        action={
                            <Link
                                href={beneficiaries.index()}
                                className={cn(
                                    buttonVariants({ size: 'compact' }),
                                    'self-start sm:self-auto',
                                )}
                            >
                                <UserRoundPlusIcon className="size-4" />
                                Add beneficiaries
                            </Link>
                        }
                    />
                    <ConsultationOverview />
                    <AllocationFallbackCard />
                    <ConsultationHistory />
                </div>
            </PortalShell>
        </>
    );
}
