import { Head } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { DashboardLayout } from '@/layouts/dashboard';
import type { BusinessConsultationReportPageProps } from '@/types';
import { AllocationFallbackCard } from '../../consultations/partials/allocation-fallback';
import { ReportExport } from './partials/report-export';
import { ReportOverview } from './partials/report-overview';
import { WorkforceStatusCard } from './partials/workforce-status-card';

export default function BusinessConsultations({
    report,
}: BusinessConsultationReportPageProps) {
    return (
        <>
            <Head title="Reports" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Reports"
                        description="Utilization and coverage analytics for your organization."
                        action={<ReportExport workforce={report.workforce} />}
                    />

                    <ReportOverview stats={report.stats} />

                    <section className="grid gap-5 pt-4 lg:grid-cols-2">
                        <WorkforceStatusCard workforce={report.workforce} />
                        <AllocationFallbackCard className="mt-0 self-start" />
                    </section>
                </div>
            </DashboardLayout>
        </>
    );
}
