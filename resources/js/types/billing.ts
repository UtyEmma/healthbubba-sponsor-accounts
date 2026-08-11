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
    cadence: string;
    currency: string;
    is_current: boolean;
    checkout_available: boolean;
    included_seats: number | null;
    additional_seat_price: string | null;
    allows_capacity_purchases: boolean;
    capacity: PlanCapacity | null;
    unavailable_reason: string | null;
    features: PlanFeature[];
    quotas: PlanQuota[];
};

export type PlanCapacity = {
    unit: string;
    unit_plural: string;
    included: number;
    maximum: number | null;
    additional_unit_price: string | null;
    purchases_enabled: boolean;
    unavailable_reason: string | null;
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
    startsAt: string | null;
    endsAt: string | null;
    trialEndsAt: string | null;
    cancelledAt: string | null;
    renewedAt: string | null;
    autoRenew: boolean;
    nextChargeAt: string | null;
    capacityCount: number;
    renewalAttempts: number;
    renewalAmount: string;
};

export type CapacityPurchaseSummary = {
    subscription_id: number;
    unit: string;
    unit_plural: string;
    current_capacity: number;
    included_capacity: number;
    maximum_capacity: number | null;
    unit_price: string | null;
    prorated_unit_price: string | null;
    currency: string;
    wallet_balance: string;
    available: boolean;
    unavailable_reason: string | null;
    term_ends_at: string | null;
};

export type PlanBillingPageProps = {
    accountType: AccountType;
    accountTypeLabel: string;
    plans: Plan[];
    subscription: SubscriptionSummary | null;
    capacityPurchase: CapacityPurchaseSummary | null;
};
