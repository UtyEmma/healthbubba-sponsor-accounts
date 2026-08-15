import type { WorkspaceActivity } from './activity';
import type { AccountType } from './billing';
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
