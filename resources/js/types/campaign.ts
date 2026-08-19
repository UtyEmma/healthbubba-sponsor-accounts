import type {
    ConsultationCoverage,
    PaginatedConsultations,
} from './consultation';
import type { Workspace } from './workspace';
import type {
    PaginatedWorkspaceBeneficiaries,
    PaginationLink,
    WorkspaceCapacity,
} from './workspace-beneficiary';

export type CampaignStatus = 'PENDING' | 'IN_PROGRESS' | 'COMPLETED';

export type CampaignBeneficiaryCapacity = WorkspaceCapacity;

export interface Campaign {
    id: number;
    name: string;
    slug: string;
    country: string | null;
    city: string | null;
    state: string | null;
    location: string | null;
    targetAudience: string | null;
    beneficiaryLimit: number;
    startDate: string | null;
    endDate: string | null;
    status: CampaignStatus;
    statusLabel: string;
    boothRequired: boolean;
    gpFee: string | null;
    specialistFee: string | null;
    beneficiaryCount?: number;
    activeBeneficiaryCount?: number;
    capacityUsed?: number;
    capacityRemaining?: number;
    createdAt: string | null;
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
}

export interface InstitutionalCampaignShowPageProps {
    organization: Workspace;
    campaign: Campaign;
    beneficiaries: PaginatedWorkspaceBeneficiaries;
    capacity: CampaignBeneficiaryCapacity;
    coverage: ConsultationCoverage;
    consultations: PaginatedConsultations;
}
