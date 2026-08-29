import type {
    EmployeeImportResult,
    PaginationLink,
} from './workspace-beneficiary';

export type InstitutionalBeneficiaryStatus =
    'added' | 'invited' | 'registered' | 'active' | 'inactive' | 'suspended';

export interface InstitutionalCampaignOption {
    name: string;
    slug: string;
    location: string | null;
    endDate: string | null;
    estimatedBeneficiaries?: number | null;
    beneficiaryLimit?: number | null;
    defaultLimit?: number;
    ended?: boolean;
}

export interface InstitutionalBeneficiary {
    publicId: string;
    firstName: string;
    lastName: string;
    name: string;
    email: string;
    phone: string;
    community: string | null;
    status: InstitutionalBeneficiaryStatus;
    accessStatus: string;
    source: string;
    hasHealthBubbaAccount: boolean;
    campaign: { name: string; slug: string } | null;
}

export interface Paginator<T> {
    data: T[];
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

export interface InstitutionalBeneficiaryPageProps {
    roster: {
        beneficiaries: Paginator<InstitutionalBeneficiary>;
        counts: Record<InstitutionalBeneficiaryStatus, number>;
        campaigns: InstitutionalCampaignOption[];
    };
    filters: {
        search?: string;
        campaign?: string;
        status?: InstitutionalBeneficiaryStatus;
    };
    importResult: InstitutionalImportResult | null;
}

export interface InstitutionalImportResult extends EmployeeImportResult {
    campaignSlug: string;
}

export type InstitutionalConsultationStatus =
    'scheduled' | 'completed' | 'cancelled';

export interface InstitutionalConsultation {
    id: number;
    date: string | null;
    beneficiary: string;
    campaign: { name: string; slug: string } | null;
    type: 'gp' | 'specialist';
    typeLabel: string;
    status: InstitutionalConsultationStatus;
    statusLabel: string;
    paymentSource: 'sponsor_coverage' | 'personal';
    paymentSourceLabel: string;
}

export interface InstitutionalConsultationPageProps {
    consultations: Paginator<InstitutionalConsultation>;
    campaigns: Array<{ name: string; slug: string }>;
    filters: { campaign?: string };
}

export type EnrollmentCodeStatus = 'active' | 'full' | 'expired';

export interface CampaignEnrollmentCode {
    id: string;
    code: string;
    enrollmentLimit: number;
    enrolled: number;
    expiresAt: string;
    status: EnrollmentCodeStatus;
    statusLabel: string;
    campaign: {
        name: string;
        slug: string;
        location: string | null;
    };
}

export interface EnrollmentCodePageProps {
    enrollmentCodes: {
        codes: CampaignEnrollmentCode[];
        campaigns: InstitutionalCampaignOption[];
    };
}

export interface InstitutionalCampaignReportRow {
    name: string;
    slug: string;
    location: string | null;
    status: string;
    statusLabel: string;
    gpUsed: number;
    gpAllocated: number;
    specialistUsed: number;
    specialistAllocated: number;
    specialistUnitFee: string;
    gpUnitFee: string;
    medicationUsed: string;
    medicationAllocated: string;
    laboratoryUsed: string;
    allocated: string;
    utilized: string;
    remaining: string | null;
    returned: string | null;
    people: number;
    utilizationPercentage: number;
}

export interface InstitutionalCommunityReportRow {
    state: string | null;
    lga: string | null;
    ward: string | null;
    community: string;
    beneficiaries: number;
    consultations: number;
}

export interface InstitutionalReportsPageProps {
    reports: {
        byCampaign: InstitutionalCampaignReportRow[];
        community: InstitutionalCommunityReportRow[];
        impact: {
            reach: number;
            utilizationPercentage: number;
            fundsDeployed: string;
            consultationsEnabled: number;
            completionPercentage: number;
            averageConsultationCost: string;
        };
        exports: Array<{
            type: 'beneficiaries' | 'coverage' | 'utilization' | 'referrals';
            title: string;
            description: string;
            available: boolean;
        }>;
    };
}
