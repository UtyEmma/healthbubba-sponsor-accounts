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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';

type CoverageDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onComplete: (message: string) => void;
};

export function RenewCoverageDialog({
    open,
    onOpenChange,
    onComplete,
}: CoverageDialogProps) {
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        onComplete('Coverage renewal confirmed for 12 months.');
    }

    return (
        <CoverageDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Renew coverage"
            description={
                <>
                    Extend Community Health Program 2026 for another term.
                    <br />
                    Purchased units and rules carry over.
                </>
            }
        >
            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <FormGroup label="Renewal term">
                        <Select defaultValue="12">
                            <option value="6">6 months</option>
                            <option value="12">12 months</option>
                            <option value="24">24 months</option>
                        </Select>
                    </FormGroup>
                    <FormGroup label="End Date">
                        <Input type="date" required />
                    </FormGroup>
                </div>
                <CoverageDialogFooter action="Confirm renewal" />
            </form>
        </CoverageDialog>
    );
}

export function TopUpCoverageDialog({
    open,
    onOpenChange,
    onComplete,
}: CoverageDialogProps) {
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const units = Number(data.get('units'));
        const service = String(
            data.get('service') || 'Scheduled consultations',
        );
        onComplete(`${units} ${service} units added.`);
    }

    return (
        <CoverageDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Top up coverage"
            description="Purchase additional consultation units for the pool."
        >
            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <FormGroup label="Service type">
                        <Select
                            name="service"
                            defaultValue="Scheduled consultations"
                        >
                            <option>Scheduled consultations</option>
                            <option>Instant consultations</option>
                        </Select>
                    </FormGroup>
                    <FormGroup label="Units to add">
                        <Input
                            name="units"
                            type="number"
                            min="1"
                            step="1"
                            required
                        />
                    </FormGroup>
                </div>
                <CoverageDialogFooter action="Add Units" />
            </form>
        </CoverageDialog>
    );
}

export function UpgradeCoverageDialog({
    open,
    onOpenChange,
    onComplete,
}: CoverageDialogProps) {
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        onComplete('Coverage model upgrade confirmed.');
    }

    return (
        <CoverageDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Upgrade coverage"
            description="Move to a higher coverage model and adjust per-beneficiary limits. This changes how care is allocated — separate from topping up units."
        >
            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <FormGroup label="Coverage model">
                        <Select defaultValue="shared">
                            <option value="shared">Shared coverage pool</option>
                            <option value="allocated">
                                Per-beneficiary allocation
                            </option>
                        </Select>
                    </FormGroup>
                    <FormGroup label="Scheduled consultation limit / beneficiary">
                        <Input type="number" min="1" step="1" required />
                    </FormGroup>
                    <FormGroup label="Instant consultation limit / beneficiary">
                        <Input type="number" min="1" step="1" required />
                    </FormGroup>
                </div>
                <CoverageDialogFooter action="Confirm upgrade" />
            </form>
        </CoverageDialog>
    );
}

function CoverageDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: ReactNode;
    children: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="border-b px-6 py-5">
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

function CoverageDialogFooter({ action }: { action: string }) {
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
                {action}
            </Button>
        </DialogFooter>
    );
}

function FormGroup({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-2 text-[13px] font-medium">
            {label}
            {children}
        </label>
    );
}
