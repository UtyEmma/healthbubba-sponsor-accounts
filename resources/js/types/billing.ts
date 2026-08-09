export type AccountType = 'individual' | 'business' | 'institution';

export type BillingPlanAction = 'current' | 'downgrade' | 'upgrade' | 'select';

export type FeatureType = 'toggle' | 'limit' | 'consumable' | 'metered'

export type Feature = {
    name: string;
    slug: string;
    description: string | null;
    type: FeatureType;
    limits?: FeatureLimit
};

export interface FeatureLimit {
    reset_interval: null,
    unit_price?: string
    value: string
}

export type Plan = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    is_current: boolean;
    features: Feature[];
    quotas: QuotaFeature[]
};

export type QuotaFeature = {
    name: string;
    quota: string;
    slug: string;
    description: string | null;
}

export type SubscriptionSummary = {
    id: number;
    status: 'active' | 'trialing' | 'past_due' | 'cancelled' | 'expired';
    statusLabel: string;
    isValid: boolean;
    plan: {
        id: number;
        name: string;
        price: string;
        billingLabel: string;
    } | null;
    startsAt: string;
    endsAt: string | null;
    trialEndsAt: string | null;
    cancelledAt: string | null;
    renewedAt: string | null;
};

export type PlanBillingPageProps = {
    accountType: AccountType;
    accountTypeLabel: string;
    plans: BillingPlan[];
    subscription: SubscriptionSummary | null;
};
