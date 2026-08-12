export type MedicalAccessDataType =
    'CLINICAL_RECORD' | 'PRESCRIPTION_RECORD' | 'LAB_RECORD';

export type MedicalAccessRequestStatus =
    'pending' | 'approved' | 'denied' | 'expired';

export interface MedicalAccessDataTypeOption {
    value: MedicalAccessDataType;
    label: string;
}

export interface MedicalAccessBeneficiary {
    publicId: string;
    name: string;
    email: string;
}

export interface MedicalAccessRequest {
    publicId: string;
    beneficiary: {
        name: string;
        email: string;
    };
    workspace: {
        name: string;
    };
    requestedByName: string | null;
    dataType: MedicalAccessDataTypeOption;
    reason: string | null;
    status: MedicalAccessRequestStatus;
    requestedAt: string;
    reviewExpiresAt: string;
    approvedAt: string | null;
    deniedAt: string | null;
    accessExpiresAt: string | null;
}

export interface MedicalAccessPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedMedicalAccessRequests {
    data: MedicalAccessRequest[];
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
        links: MedicalAccessPaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface MedicalAccessPageProps {
    requests: PaginatedMedicalAccessRequests;
    beneficiaries: MedicalAccessBeneficiary[];
    dataTypes: MedicalAccessDataTypeOption[];
}
