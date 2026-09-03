import type { AccountType } from './billing';
import type { Wallet } from './wallet';

export type AllocationFallback = 'beneficiary_wallet' | 'card_payment';
export type InstitutionalOrganizationType =
    | 'ngo_foundation'
    | 'government_agency'
    | 'development_partner'
    | 'faith_based_organization'
    | 'community_based_organization'
    | 'corporate_foundation'
    | 'other';

export interface Workspace {
    id: number;
    name: string;
    logo?: string | null;
    description?: string | null;
    onboardedAt: string | null;
    type: AccountType;
    organizationType: InstitutionalOrganizationType | null;
    organizationTypeLabel: string | null;
    countryCode: string | null;
    stateCode: string | null;
    stateLabel: string | null;
    officialEmail: string | null;
    officialPhone: string | null;
    fallbackChannel: AllocationFallback | null;
    wallet?: Wallet | null;
}
