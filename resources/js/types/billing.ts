export type AccountType = 'individual' | 'business' | 'institution';

export type FeatureType = 'toggle' | 'limit' | 'consumable' | 'metered';

export type PlanFeature = {
    name: string;
    slug: string;
    description: string | null;
    type: FeatureType;
    included: boolean;
    value: string | null;
    unitPrice: string | null;
};

export type Plan = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    is_current: boolean;
    features: PlanFeature[];
    quotas: PlanQuota[];
};

export type PlanQuota = {
    name: string;
    slug: string;
    quota: string | null;
    description: string;
};

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
    plans: Plan[];
    subscription: SubscriptionSummary | null;
};
