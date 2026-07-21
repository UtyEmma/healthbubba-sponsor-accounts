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

export type WalletTransaction = {
    id: number;
    direction: 'in' | 'out';
    description: string;
    type: string;
    date: string;
    amount: number;
};

const currency = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
});

export function WalletSummary({
    balance,
    totalIn,
    totalOut,
}: {
    balance: number;
    totalIn: number;
    totalOut: number;
}) {
    return (
        <section
            className="grid gap-5 pt-6 md:grid-cols-3"
            aria-label="Wallet summary"
        >
            <SummaryCard label="Available balance" value={balance} />
            <SummaryCard label="Total in (90d)" value={totalIn} />
            <SummaryCard label="Total out (90d)" value={totalOut} />
        </section>
    );
}

function SummaryCard({ label, value }: { label: string; value: number }) {
    return (
        <Card className="min-h-[94px]">
            <CardContent className="px-5 py-5">
                <p className="text-sm leading-5 text-muted-foreground">
                    {label}
                </p>
                <p className="text-2xl leading-8 font-semibold tracking-[-0.4px]">
                    {currency.format(value)}
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
                        Sponsors can fund and transfer out; clinical payments
                        are made by beneficiaries from their own wallets.
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
                            {transactions.map((transaction) => (
                                <TableRow key={transaction.id}>
                                    <TableCell className="h-[61px] pl-8 font-medium">
                                        <span className="flex items-center gap-3">
                                            <span
                                                className={
                                                    transaction.direction ===
                                                    'in'
                                                        ? 'flex size-8 items-center justify-center rounded-full text-success'
                                                        : 'flex size-8 items-center justify-center rounded-full bg-muted text-muted-foreground'
                                                }
                                            >
                                                {transaction.direction ===
                                                'in' ? (
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
                                        {transaction.date}
                                    </TableCell>
                                    <TableCell className="h-[61px] pr-8 whitespace-nowrap text-muted-foreground">
                                        {currency.format(transaction.amount)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </section>
    );
}
