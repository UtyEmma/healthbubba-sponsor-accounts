import type { AccountType } from './billing';
import type { Wallet } from './wallet';

export type User = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar?: string;
    wallet?: Wallet | null;
    email_verified_at: string | null;
    phone_verified_at: string | null;
    account_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
};

export type AccountAvailabilityStatus =
    | 'new_identity'
    | 'existing_identity'
    | 'owned_workspace'
    | 'member_workspace';

export interface AccountAvailability {
    status: AccountAvailabilityStatus;
    canLogin: boolean;
    canSetup: boolean;
}

export interface AccountSetupAuthentication {
    canLogin: boolean;
    loginRedirect: string | null;
}

export interface AuthEntryPageProps {
    initialAccountType: AccountType | null;
    initialEmail: string;
}
