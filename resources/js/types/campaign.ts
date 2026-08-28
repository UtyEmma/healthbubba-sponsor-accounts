import type {
    ConsultationCoverage,
    PaginatedConsultations,
} from './consultation';
import type { Workspace } from './workspace';
import type {
    EmployeeImportResult,
    PaginatedWorkspaceBeneficiaries,
    PaginationLink,
    WorkspaceCapacity,
} from './workspace-beneficiary';

export type CampaignStatus = 'PENDING' | 'IN_PROGRESS' | 'PAUSED' | 'COMPLETED';

export type CampaignEnrollmentMethod = 'upload' | 'manual';

export interface CampaignConsultationMetric {
    units: number;
    confirmed: number;
    reserved: number;
    remaining: number;
    unitFee: string;
    allocated: string;
}

export interface CampaignBudgetMetric {
    allocated: string;
    used: string;
    remaining: string;
}

export interface CampaignFinancialMetrics {
    currency: string;
    allocated: string;
    utilized: string;
    reserved: string;
    returned: string;
    utilizationPercentage: number;
    consultations: {
        gp: CampaignConsultationMetric;
        specialist: CampaignConsultationMetric;
    };
    budgets: {
        medication: CampaignBudgetMetric;
        laboratory: CampaignBudgetMetric;
    };
}

export interface CampaignBooth {
    count: number;
    preferredDeploymentDate: string | null;
    site: string | null;
    contactName: string | null;
    contactPhone: string | null;
    setupUnitFee: string | null;
    monthlyUnitFee: string | null;
    activatedAt: string | null;
    deactivatedAt: string | null;
}

export type CampaignBeneficiaryCapacity = WorkspaceCapacity;

export interface Campaign {
    id: number;
    name: string;
    description: string | null;
    slug: string;
    country: string | null;
    city: string | null;
    state: string | null;
    location: string | null;
    targetAudience: string | null;
    enrollmentMethod: CampaignEnrollmentMethod | null;
    estimatedBeneficiaries: number | null;
    beneficiaryLimit: number | null;
    startDate: string | null;
    endDate: string | null;
    status: CampaignStatus;
    statusLabel: string;
    boothRequired: boolean;
    booth: CampaignBooth | null;
    gpFee: string | null;
    specialistFee: string | null;
    beneficiaryCount?: number;
    activeBeneficiaryCount?: number;
    capacityUsed?: number;
    capacityRemaining?: number;
    financial?: CampaignFinancialMetrics;
    launchedAt: string | null;
    pausedAt: string | null;
    endedAt: string | null;
    createdAt: string | null;
}

export interface CampaignIndexSummary {
    currency: string;
    availableBalance: string;
    allocatedBalance: string;
    allocatedCampaigns: number;
    utilized: string;
    enrolledBeneficiaries: number;
}

export interface CampaignCreationConfiguration {
    currency: string;
    walletBalance: string;
    gpUnitFee: string;
    specialistUnitFee: string;
    boothSetupUnitFee: string;
    boothMonthlyUnitFee: string;
}

export interface CampaignConsultationQuota {
    id: number;
    consultationType: string;
    quantity: number;
    unitFee: string;
    totalCost: string;
    reference: string;
    createdAt: string;
}

export interface CampaignFinancialSummary {
    currency: string;
    walletBalance: string;
    gpSpent: string;
    specialistSpent: string;
    totalSpent: string;
}

export interface CampaignConsultationSummary {
    coverage: ConsultationCoverage;
    financialSummary: CampaignFinancialSummary;
}

export interface PaginatedCampaigns {
    data: Campaign[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface InstitutionalCampaignIndexPageProps {
    organization: Workspace;
    campaigns: PaginatedCampaigns;
    summary: CampaignIndexSummary;
    creation: CampaignCreationConfiguration;
}

export interface InstitutionalCampaignShowPageProps {
    organization: Workspace;
    campaign: Campaign;
    beneficiaries: PaginatedWorkspaceBeneficiaries;
    capacity: CampaignBeneficiaryCapacity;
    campaignConsultation: CampaignConsultationSummary;
    consultations: PaginatedConsultations;
    importResult: EmployeeImportResult | null;
}
