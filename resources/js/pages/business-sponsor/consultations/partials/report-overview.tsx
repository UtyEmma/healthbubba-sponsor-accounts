import { ActivityIcon, StethoscopeIcon, UsersRoundIcon } from 'lucide-react';

import type { BusinessConsultationReport } from '@/types';

import { BusinessMetricCard } from '../../partials/business-metric-card';

export function ReportOverview({
    stats,
}: {
    stats: BusinessConsultationReport['stats'];
}) {
    const gpConsultations = stats.gpConsultations.unavailableReason
        ? 'Unavailable'
        : stats.gpConsultations.unlimited
          ? 'Unlimited'
          : String(stats.gpConsultations.remaining ?? 0);

    return (
        <section
            className="grid gap-4 pt-6 sm:grid-cols-2 lg:grid-cols-3"
            aria-label="Report overview"
        >
            <BusinessMetricCard
                label="Active Employees"
                value={String(stats.activeEmployees)}
                icon={UsersRoundIcon}
                tone="green"
            />
            <BusinessMetricCard
                label="Scheduled consultations left"
                value={gpConsultations}
                icon={StethoscopeIcon}
                tone="blue"
            />
            <BusinessMetricCard
                label="Activation rate"
                value={`${stats.activationRate}%`}
                icon={ActivityIcon}
            />
        </section>
    );
}
