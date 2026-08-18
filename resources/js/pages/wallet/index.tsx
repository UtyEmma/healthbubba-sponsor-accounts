import { Head, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { PaymentStatusNotice } from '@/components/payment-status-notice';
import { PortalShell } from '@/components/portal-shell';
import type { WalletPageProps } from '@/types';

import { AddFundsDialog } from './partials/wallet-dialogs';
import { TransactionsCard, WalletSummary } from './partials/wallet-overview';
import { DashboardLayout } from '@/layouts/dashboard';

export default function WalletIndex({ wallet, transactions }: WalletPageProps) {
    const { errors, flash } = usePage().props;

    return (
        <>
            <Head title="Wallet" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Wallet"
                        description="Fund your workspace wallet securely for out-of-allocation care."
                        action={<AddFundsDialog />}
                    />

                    <PaymentStatusNotice
                        success={flash.success}
                        error={errors.payment}
                    />

                    <WalletSummary wallet={wallet} />

                    <TransactionsCard transactions={transactions} />
                </div>
            </DashboardLayout>
        </>
    );
}
