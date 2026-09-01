import type { WorkspaceActivity } from './activity';
import type { AccountType } from './billing';
import type {
    CampaignBudgetMetric,
    CampaignConsultationMetric,
} from './campaign';
import type { ConsultationCoverage } from './consultation';

export interface DashboardSubscription {
    planName: string;
    status: 'active' | 'trialing' | 'past_due' | 'cancelled' | 'expired';
    statusLabel: string;
    active: boolean;
    renewalAmount: string;
    billingCycleLabel: string;
    includedCapacity: number;
    currentCapacity: number;
    additionalCapacity: number;
    renewsAt: string | null;
    renewalDays: number | null;
}

export interface DashboardDepartmentUtilization {
    department: string;
    gp: number;
    specialist: number;
}

export interface DashboardConsultationTrend {
    month: string;
    consultations: number;
}

export interface WorkspaceDashboard {
    accountType: AccountType;
    beneficiaries: {
        total: number;
        active: number;
        capacity: number;
    };
    wallet: {
        balance: string;
        currency: string;
        totalFunded: string;
    };
    subscription: DashboardSubscription | null;
    coverage: ConsultationCoverage;
    completedConsultations: number;
    departmentUtilization: DashboardDepartmentUtilization[];
    consultationTrends: DashboardConsultationTrend[];
    recentActivities: WorkspaceActivity[];
}

export interface DashboardPageProps {
    dashboard: WorkspaceDashboard;
}

export type InstitutionalDashboardBoothStatus =
    'requested' | 'active' | 'grace_period' | 'suspended' | 'inactive';

export interface InstitutionalDashboardBooth {
    id: string;
    name: string;
    community: string;
    campaignName: string;
    campaignSlug: string;
    status: InstitutionalDashboardBoothStatus;
    statusLabel: string;
    activatedAt: string | null;
    nextDeduction: string | null;
    monthlyFee: string;
    outstandingAmount: string | null;
    graceEndsOn: string | null;
    campaignEndsOn: string | null;
}

export interface InstitutionalDashboardCampaign {
    id: number;
    name: string;
    slug: string;
    status: 'PENDING' | 'IN_PROGRESS' | 'PAUSED' | 'COMPLETED';
    statusLabel: string;
    allocated: string;
    utilized: string;
    remaining: string;
    people: number;
}

export interface InstitutionalDashboardConsultationMetric {
    allocated: number;
    used: number;
    reserved: number;
    remaining: number;
}

export interface InstitutionalDashboardActivity {
    id: string;
    title: string;
    actorName: string;
    occurredAt: string | null;
}

export interface InstitutionalDashboardRemainingCampaign {
    id: number;
    name: string;
    slug: string;
    currency: string;
    gp: CampaignConsultationMetric;
    specialist: CampaignConsultationMetric;
    medication: CampaignBudgetMetric;
}

export interface InstitutionalDashboardData {
    funding: {
        currency: string;
        availableBalance: string;
        allocatedBalance: string;
        utilized: string;
    };
    beneficiaries: { total: number; active: number };
    booths: {
        summary: {
            operational: number;
            awaitingDeployment: number;
            monthlyServiceCost: string;
            serviceUnitFee: string;
            nextDeduction: string | null;
            walletBalance: string;
            currency: string;
            delinquentCount: number;
            outstandingAmount: string;
        };
        rows: InstitutionalDashboardBooth[];
    };
    campaignPerformance: InstitutionalDashboardCampaign[];
    consultations: {
        gp: InstitutionalDashboardConsultationMetric;
        specialist: InstitutionalDashboardConsultationMetric;
    };
    consultationTrends: DashboardConsultationTrend[];
    activities: InstitutionalDashboardActivity[];
    remainingCampaigns: InstitutionalDashboardRemainingCampaign[];
}

export interface InstitutionalDashboardPageProps {
    dashboard: InstitutionalDashboardData;
}
