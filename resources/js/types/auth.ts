import type { Wallet } from './wallet';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    wallet?: Wallet | null;
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
};
