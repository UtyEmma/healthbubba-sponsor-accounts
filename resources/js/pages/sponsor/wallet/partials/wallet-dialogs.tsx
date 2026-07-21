import { PlusIcon, SendIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export function AddFundsDialog({ onAdd }: { onAdd: (amount: number) => void }) {
    const [open, setOpen] = useState(false);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const amount = Number(new FormData(event.currentTarget).get('amount'));

        if (amount <= 0) {
            return;
        }

        onAdd(amount);
        setOpen(false);
    }

    return (
        <WalletDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button size="compact">
                    <PlusIcon className="size-4" />
                    Add funds
                </Button>
            }
            title="Add funds"
            description="Top up your wallet balance instantly."
        >
            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <AmountField />
                    <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
                        Payment method
                        <Select defaultValue="card">
                            <SelectTrigger className="h-10 w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="card">Card</SelectItem>
                                <SelectItem value="bank">
                                    Bank transfer
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </label>
                </div>
                <WalletDialogFooter submitLabel="Add funds" />
            </form>
        </WalletDialog>
    );
}

export function TransferFundsDialog({
    onTransfer,
}: {
    onTransfer: (beneficiary: string, amount: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const [beneficiary, setBeneficiary] = useState<string | null>(null);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const amount = Number(new FormData(event.currentTarget).get('amount'));

        if (!beneficiary || amount <= 0) {
            return;
        }

        onTransfer(beneficiary, amount);
        setBeneficiary(null);
        setOpen(false);
    }

    return (
        <WalletDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button variant="outline" size="compact">
                    <SendIcon className="size-4" />
                    Transfer
                </Button>
            }
            title="Transfer to beneficiary"
            description="Move funds from your wallet to a beneficiary's wallet."
        >
            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
                        Beneficiary
                        <Select
                            value={beneficiary}
                            onValueChange={setBeneficiary}
                            required
                        >
                            <SelectTrigger className="h-10 w-full">
                                <SelectValue placeholder="Select beneficiary" />
                            </SelectTrigger>
                            <SelectContent>
                                {[
                                    'Chidi Okafor',
                                    'Ngozi Okafor',
                                    'Jane Okafor',
                                ].map((name) => (
                                    <SelectItem key={name} value={name}>
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </label>
                    <AmountField />
                </div>
                <WalletDialogFooter submitLabel="Send Transfer" />
            </form>
        </WalletDialog>
    );
}

function AmountField() {
    return (
        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
            Amount
            <Input
                name="amount"
                type="number"
                min="1"
                step="1"
                required
                className="h-10"
            />
        </label>
    );
}

function WalletDialogFooter({ submitLabel }: { submitLabel: string }) {
    return (
        <DialogFooter className="flex-row justify-end border-t px-6 py-4">
            <DialogClose
                render={
                    <Button type="button" variant="outline" size="compact" />
                }
            >
                Cancel
            </DialogClose>
            <Button type="submit" size="compact">
                {submitLabel}
            </Button>
        </DialogFooter>
    );
}

function WalletDialog({
    open,
    onOpenChange,
    trigger,
    title,
    description,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger: ReactNode;
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger render={trigger as React.ReactElement} />
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        {title}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {description}
                    </DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}
