import { ArrowDownLeftIcon, ArrowUpRightIcon } from 'lucide-react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { WalletSummary as WalletSummaryData } from '@/types';
import type { WalletTransaction } from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : 'Pending';
}

export function WalletSummary({ wallet }: { wallet: WalletSummaryData }) {
    return (
        <section
            className="grid gap-5 pt-6 md:grid-cols-3"
            aria-label="Wallet summary"
        >
            <SummaryCard
                label="Available balance"
                value={wallet.balance}
                currency={wallet.currency}
            />
            <SummaryCard
                label="Total in (90d)"
                value={wallet.total_in}
                currency={wallet.currency}
            />
            <SummaryCard
                label="Total out (90d)"
                value={wallet.total_out}
                currency={wallet.currency}
            />
        </section>
    );
}

function SummaryCard({
    label,
    value,
    currency,
}: {
    label: string;
    value: string;
    currency: string;
}) {
    return (
        <Card className="min-h-[94px]">
            <CardContent className="px-5 py-5">
                <p className="text-sm leading-5 text-muted-foreground">
                    {label}
                </p>
                <p className="text-2xl leading-8 font-semibold tracking-[-0.4px]">
                    {formatMoney(value, currency)}
                </p>
            </CardContent>
        </Card>
    );
}

export function TransactionsCard({
    transactions,
}: {
    transactions: WalletTransaction[];
}) {
    return (
        <section className="pt-4" aria-label="Transactions">
            <Card>
                <CardHeader className="gap-1 px-6 py-4">
                    <CardTitle className="text-base leading-6 font-semibold">
                        Transactions
                    </CardTitle>
                    <CardDescription className="leading-5">
                        Verified wallet funding and subscription ledger
                        activity.
                    </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto p-0">
                    <Table className="min-w-[760px]">
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-[33%] pl-8">
                                    Description
                                </TableHead>
                                <TableHead className="w-[33%]">Type</TableHead>
                                <TableHead className="w-[33%]">Date</TableHead>
                                <TableHead className="pr-8">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.length > 0 ? (
                                transactions.map((transaction) => (
                                    <TableRow key={transaction.id}>
                                        <TableCell className="h-[61px] pl-8 font-medium">
                                            <span className="flex items-center gap-3">
                                                <span
                                                    className={
                                                        transaction.flow ===
                                                        'credit'
                                                            ? 'flex size-8 items-center justify-center rounded-full text-success'
                                                            : 'flex size-8 items-center justify-center rounded-full bg-muted text-muted-foreground'
                                                    }
                                                >
                                                    {transaction.flow ===
                                                    'credit' ? (
                                                        <ArrowDownLeftIcon className="size-4" />
                                                    ) : (
                                                        <ArrowUpRightIcon className="size-4" />
                                                    )}
                                                </span>
                                                {transaction.description}
                                            </span>
                                        </TableCell>
                                        <TableCell className="h-[61px] text-muted-foreground">
                                            {transaction.type}
                                        </TableCell>
                                        <TableCell className="h-[61px] text-muted-foreground">
                                            {formatDate(
                                                transaction.occurred_at,
                                            )}
                                        </TableCell>
                                        <TableCell className="h-[61px] pr-8 whitespace-nowrap text-muted-foreground">
                                            {formatMoney(
                                                transaction.amount,
                                                transaction.currency,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No wallet transactions yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </section>
    );
}
