import type { Workspace } from './workspace';
import type { PaginationLink } from './workspace-beneficiary';

export type CampaignStatus = 'PENDING' | 'IN_PROGRESS' | 'COMPLETED';

export interface Campaign {
    id: number;
    name: string;
    slug: string;
    country: string | null;
    city: string | null;
    state: string | null;
    location: string | null;
    targetAudience: string | null;
    startDate: string | null;
    endDate: string | null;
    status: CampaignStatus;
    statusLabel: string;
    boothRequired: boolean;
    beneficiaryCount?: number;
    activeBeneficiaryCount?: number;
    createdAt: string | null;
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
}
