import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';

import { AddFundsDialog, TransferFundsDialog } from './partials/wallet-dialogs';
import { TransactionsCard, WalletSummary } from './partials/wallet-overview';
import type { WalletTransaction } from './partials/wallet-overview';

const initialTransactions: WalletTransaction[] = [
    {
        id: 1,
        direction: 'in',
        description: 'Wallet top-up (card ****4242)',
        type: 'Top Up',
        date: '18 Jun, 2026',
        amount: 50000,
    },
    {
        id: 2,
        direction: 'out',
        description: 'Chidi Okafor',
        type: 'Prescription records',
        date: '18 Jun, 2026',
        amount: 50000,
    },
];

export default function WalletIndex() {
    const [balance, setBalance] = useState(45000);
    const [totalIn, setTotalIn] = useState(50000);
    const [totalOut, setTotalOut] = useState(5000);
    const [transactions, setTransactions] = useState(initialTransactions);
    const [announcement, setAnnouncement] = useState('');

    function addFunds(amount: number) {
        setBalance((current) => current + amount);
        setTotalIn((current) => current + amount);
        addTransaction({
            direction: 'in',
            description: 'Wallet top-up (card ****4242)',
            type: 'Top Up',
            amount,
        });
        setAnnouncement(
            `Added ₦${amount.toLocaleString('en-NG')} to the wallet.`,
        );
    }

    function transferFunds(beneficiary: string, amount: number) {
        setBalance((current) => Math.max(0, current - amount));
        setTotalOut((current) => current + amount);
        addTransaction({
            direction: 'out',
            description: beneficiary,
            type: 'Wallet transfer',
            amount,
        });
        setAnnouncement(
            `Transferred ₦${amount.toLocaleString('en-NG')} to ${beneficiary}.`,
        );
    }

    function addTransaction(
        transaction: Omit<WalletTransaction, 'id' | 'date'>,
    ) {
        setTransactions((current) => [
            {
                ...transaction,
                id: Math.max(...current.map(({ id }) => id)) + 1,
                date: '21 Jul, 2026',
            },
            ...current,
        ]);
    }

    return (
        <>
            <Head title="Wallet" />
            <PortalShell>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Wallet"
                        description="Fund your wallet and transfer to beneficiaries for out-of-allocation care."
                        action={
                            <div className="flex items-center gap-3 self-start sm:self-auto">
                                <TransferFundsDialog
                                    onTransfer={transferFunds}
                                />
                                <AddFundsDialog onAdd={addFunds} />
                            </div>
                        }
                    />

                    <WalletSummary
                        balance={balance}
                        totalIn={totalIn}
                        totalOut={totalOut}
                    />
                    <TransactionsCard transactions={transactions} />

                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </PortalShell>
        </>
    );
}
