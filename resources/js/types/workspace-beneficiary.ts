export type WorkspaceBeneficiaryStatus =
    | 'pending'
    | 'active'
    | 'suspended'
    | 'revoked'
    | 'declined'
    | 'cancelled'
    | 'expired';

export type WorkspaceBeneficiaryAccessAction = 'suspend' | 'restore' | 'revoke';

export type WorkspaceBeneficiarySource =
    'manual' | 'import' | 'booth' | 'enrollment_code';

export type WorkspaceBeneficiaryRelatable =
    | {
          type: 'campaign';
          id: number;
          name: string;
          slug: string;
      }
    | {
          type: 'workspace';
          id: number;
          name: string;
          slug: null;
      };

export interface WorkspaceBeneficiary {
    id: number;
    publicId: string;
    relatable: WorkspaceBeneficiaryRelatable | null;
    firstName: string;
    lastName: string;
    name: string;
    email: string;
    phone: string;
    community: string | null;
    department: string | null;
    employeeId: string | null;
    status: WorkspaceBeneficiaryStatus;
    source: WorkspaceBeneficiarySource;
    hasHealthBubbaAccount: boolean;
    invitedAt: string;
    expiresAt: string;
    acceptedAt: string | null;
    declinedAt: string | null;
    cancelledAt: string | null;
    suspendedAt: string | null;
    revokedAt: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedWorkspaceBeneficiaries {
    data: WorkspaceBeneficiary[];
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

export interface WorkspaceCapacity {
    used: number;
    total: number;
    remaining: number;
    canInvite: boolean;
    unlimited?: boolean;
    unavailableReason: string | null;
}

export interface WorkspaceBeneficiaryCounts {
    active: number;
    pending: number;
}

export interface EmployeeImportResult {
    id: string | null;
    processed: number;
    imported: number;
    skipped: number;
    errors: Array<{
        row: number;
        identifier: string | null;
        code: string;
        message: string;
        errors: string[];
    }>;
}

export interface WorkspaceBeneficiaryPageProps {
    invitations: PaginatedWorkspaceBeneficiaries;
    capacity: WorkspaceCapacity;
    counts: WorkspaceBeneficiaryCounts;
    importResult: EmployeeImportResult | null;
}
