import type { AccountType } from './billing';

export type WorkspaceMemberRole = 'owner' | 'administrator' | 'viewer';
export type WorkspaceMemberStatus =
    'invited' | 'active' | 'disabled' | 'declined' | 'cancelled' | 'expired';

export interface WorkspaceTeamMember {
    id: string;
    name: string;
    email: string;
    role: WorkspaceMemberRole;
    roleLabel: string;
    status: WorkspaceMemberStatus;
    statusLabel: string;
    isCurrentUser: boolean;
    invitedAt: string | null;
    expiresAt: string | null;
    acceptedAt: string | null;
}

export interface WorkspaceTeamInvitationReview {
    name: string;
    email: string;
    workspaceName: string;
    role: WorkspaceMemberRole;
    roleLabel: string;
    status: WorkspaceMemberStatus;
    expiresAt: string | null;
    existingAccount: boolean;
    canAccept: boolean;
    wrongAccount: boolean;
}

export interface PaginatedWorkspaceTeamMembers {
    data: WorkspaceTeamMember[];
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
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface WorkspaceOption {
    id: number;
    name: string;
    type: AccountType;
    role: WorkspaceMemberRole;
    roleLabel: string;
    isCurrent: boolean;
}

export interface WorkspacePermissions {
    canView: boolean;
    canManage: boolean;
    canViewFinancial: boolean;
}
