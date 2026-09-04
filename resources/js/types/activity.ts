export type WorkspaceActivityActorType = 'user' | 'beneficiary' | 'system';

export type WorkspaceActivityCategory =
    'transaction' | 'subscription' | 'beneficiary' | 'medical_access';

export type WorkspaceActivityTone =
    'success' | 'warning' | 'destructive' | 'info' | 'neutral';

export type WorkspaceActivityIcon =
    | 'wallet'
    | 'credit-card'
    | 'circle-alert'
    | 'clock'
    | 'arrow-up-circle'
    | 'calendar-clock'
    | 'user-plus'
    | 'user-check'
    | 'user-x'
    | 'pause-circle'
    | 'rotate-ccw'
    | 'shield-check'
    | 'shield-x'
    | 'circle-dot';

export type WorkspaceActivityEvent =
    | 'wallet_top_up_completed'
    | 'payment_failed'
    | 'subscription_activated'
    | 'subscription_renewed'
    | 'subscription_past_due'
    | 'subscription_expired'
    | 'plan_upgrade_completed'
    | 'plan_downgrade_scheduled'
    | 'plan_downgrade_applied'
    | 'plan_downgrade_cancelled'
    | 'capacity_purchased'
    | 'beneficiary_invited'
    | 'invitation_resent'
    | 'invitation_cancelled'
    | 'employee_import_completed'
    | 'invitation_accepted'
    | 'invitation_declined'
    | 'beneficiary_suspended'
    | 'beneficiary_restored'
    | 'beneficiary_revoked'
    | 'medical_access_requested'
    | 'medical_access_approved'
    | 'medical_access_denied';

export interface WorkspaceActivityActor {
    type: WorkspaceActivityActorType;
    id: number | null;
    user_id: number | null;
    name: string;
}

export interface WorkspaceActivity {
    id: string;
    event: WorkspaceActivityEvent;
    category: WorkspaceActivityCategory;
    title: string;
    description: string | null;
    icon: WorkspaceActivityIcon;
    tone: WorkspaceActivityTone;
    actor: WorkspaceActivityActor;
    occurredAt: string;
    isUnread: boolean;
}

export interface PaginatedWorkspaceActivities {
    data: WorkspaceActivity[];
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

export interface WorkspaceActivitySummary {
    recent: WorkspaceActivity[];
    unreadCount: number;
}
