import type { CampaignStatus } from './campaign';

export type InstitutionalCoverageType = 'shared_pool' | 'per_beneficiary';
export type InstitutionalCoverageExpiry = 'annual';
export type InstitutionalPaymentPreference =
    'user_choice' | 'beneficiary_wallet' | 'card_payment';

export interface FundingSummary {
    currency: string;
    availableBalance: string;
    allocatedBalance: string;
    utilized: string;
    totalFunded: string;
    walletBalance: string;
    allocatedCampaigns: number;
}

export interface InstitutionalFundingProgram {
    name: string;
    startsOn: string;
    endsOn: string;
    status: 'active' | 'ended';
    statusLabel: string;
    coverageType: InstitutionalCoverageType;
    coverageTypeLabel: string;
    gpLimitPerBeneficiary: number;
    specialistLimitPerBeneficiary: number;
    dailyConsultationLimit: number;
    expiryCadence: InstitutionalCoverageExpiry;
    expiryCadenceLabel: string;
    paymentPreference: InstitutionalPaymentPreference;
    paymentPreferenceLabel: string;
}

export interface FundingCampaignAllocation {
    id: number;
    name: string;
    slug: string;
    location: string | null;
    status: CampaignStatus;
    statusLabel: string;
    allocated: string;
    utilized: string;
    reserved: string;
    returned: string;
    ended: boolean;
}

export interface FundingLedgerEntry {
    id: string;
    date: string | null;
    type: string;
    typeLabel: string;
    description: string;
    beneficiary: string | null;
    amount: string;
    currency: string;
    flow: 'credit' | 'debit';
}

export interface FundingOption<T extends string = string> {
    value: T;
    label: string;
}

export interface FundingConfiguration {
    coverageTypes: FundingOption<InstitutionalCoverageType>[];
    expiryCadences: FundingOption<InstitutionalCoverageExpiry>[];
    paymentPreferences: FundingOption<InstitutionalPaymentPreference>[];
    fundingMethods: FundingOption<'bank_transfer'>[];
}

export interface InstitutionalFunding {
    summary: FundingSummary;
    program: InstitutionalFundingProgram;
    campaigns: FundingCampaignAllocation[];
    returnedFromEndedCampaigns: string;
    transactions: FundingLedgerEntry[];
    transactionCount: number;
    configuration: FundingConfiguration;
}

export interface InstitutionalFundingPageProps {
    funding: InstitutionalFunding;
}
