import type { AccountType } from './billing';
import type { Wallet } from './wallet';

export type AllocationFallback = 'beneficiary_wallet' | 'card_payment';

export interface Workspace {
    id: number;
    name: string;
    logo?: string | null;
    description?: string | null;
    onboardedAt: string | null;
    type: AccountType;
    fallbackChannel: AllocationFallback | null;
    wallet?: Wallet | null;
}
