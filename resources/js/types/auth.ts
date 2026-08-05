import { Wallet } from "./wallet";

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    wallet: Wallet;    
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
};
