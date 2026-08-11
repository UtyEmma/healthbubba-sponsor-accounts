import type { AccountType } from './billing';
import type { Wallet } from './wallet';

export interface Workspace {
    id: number;
    name: string;
    logo?: string | null;
    description?: string | null;
    type: AccountType;
    wallet?: Wallet | null;
}
