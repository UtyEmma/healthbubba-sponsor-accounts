export type WorkforceStatus = 'active' | 'inactive' | 'pending' | 'suspended';

export interface WorkforceStatusSummary {
    status: WorkforceStatus;
    label: string;
    count: number;
    percentage: number;
}

export interface BusinessConsultationReport {
    stats: {
        activeEmployees: number;
        gpConsultations: {
            remaining: number | null;
            unlimited: boolean;
            unavailableReason: string | null;
        };
        activationRate: number;
    };
    workforce: WorkforceStatusSummary[];
}

export interface BusinessConsultationReportPageProps {
    report: BusinessConsultationReport;
}
