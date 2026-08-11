export interface Wallet {
    balance: string;
    currency: string;
}

export interface WalletSummary extends Wallet {
    total_in: string;
    total_out: string;
}

export type WalletTransaction = {
    id: number;
    flow: 'credit' | 'debit';
    description: string;
    type: string;
    occurred_at: string | null;
    amount: string;
    currency: string;
};

export type WalletPageProps = {
    wallet: WalletSummary;
    transactions: WalletTransaction[];
};
