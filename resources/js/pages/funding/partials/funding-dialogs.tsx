import { useForm } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { ReactNode } from 'react';

import ExtendInstitutionalFundingProgramController from '@/actions/App/Http/Controllers/Funding/ExtendInstitutionalFundingProgramController';
import StoreInstitutionalFundingController from '@/actions/App/Http/Controllers/Funding/StoreInstitutionalFundingController';
import UpdateInstitutionalCoverageRulesController from '@/actions/App/Http/Controllers/Funding/UpdateInstitutionalCoverageRulesController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import type {
    FundingConfiguration,
    FundingSummary,
    InstitutionalCoverageExpiry,
    InstitutionalCoverageType,
    InstitutionalFundingProgram,
    InstitutionalPaymentPreference,
} from '@/types';

interface ControlledDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function FundAccountDialog({
    open,
    onOpenChange,
    summary,
}: ControlledDialogProps & { summary: FundingSummary }) {
    const form = useForm({ amount: '', funding_method: 'paystack' });
    const amount = Number(form.data.amount.replaceAll(',', '') || 0);
    const projected = Number(summary.availableBalance) + amount;
    const fundingPaymentError = (
        form.errors as Record<string, string | undefined>
    ).funding_payment;

    function submit() {
        form.transform((data) => ({
            ...data,
            amount: data.amount.replaceAll(',', ''),
        }));
        form.post(StoreInstitutionalFundingController.url(), {
            preserveScroll: true,
            onError: () => onOpenChange(true),
        });
    }

    return (
        <FundingDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Fund your account"
            description="Money lands in your available balance, ready to allocate to campaigns."
            footer={
                <>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={form.processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={form.processing || amount <= 0}
                    >
                        {form.processing ? 'Opening Paystack…' : 'Fund account'}
                    </Button>
                </>
            }
        >
            <div className="grid gap-4">
                <Field label="Amount (₦)" error={form.errors.amount}>
                    <Input
                        value={form.data.amount}
                        onChange={(event) =>
                            form.setData(
                                'amount',
                                formatNumericInput(event.target.value),
                            )
                        }
                        inputMode="decimal"
                        placeholder="5,000,000"
                        disabled={form.processing}
                        className="focus-visible:border-success focus-visible:ring-success/20"
                    />
                </Field>
                <Field label="Funding method">
                    <Select value="paystack" disabled>
                        <option value="paystack">Paystack</option>
                    </Select>
                </Field>
                <div className="flex items-center justify-between rounded-lg border border-border bg-muted/40 px-3 py-3 text-sm">
                    <span className="text-muted-foreground">
                        Available after funding
                    </span>
                    <strong className="font-semibold text-success">
                        {money(projected)}
                    </strong>
                </div>
                {fundingPaymentError && (
                    <p role="alert" className="text-sm text-destructive">
                        {fundingPaymentError}
                    </p>
                )}
            </div>
        </FundingDialog>
    );
}

export function EditCoverageRulesDialog({
    open,
    onOpenChange,
    program,
    configuration,
}: ControlledDialogProps & {
    program: InstitutionalFundingProgram;
    configuration: FundingConfiguration;
}) {
    const form = useForm({
        coverage_type: program.coverageType,
        gp_limit_per_beneficiary: String(program.gpLimitPerBeneficiary),
        specialist_limit_per_beneficiary: String(
            program.specialistLimitPerBeneficiary,
        ),
        daily_consultation_limit: String(program.dailyConsultationLimit),
        expiry_cadence: program.expiryCadence,
        payment_preference: program.paymentPreference,
    });

    function submit() {
        form.patch(UpdateInstitutionalCoverageRulesController.url(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
            onError: () => onOpenChange(true),
        });
    }

    return (
        <FundingDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Edit coverage rules"
            description="These defaults govern how beneficiaries draw on campaign allocations."
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button onClick={submit} disabled={form.processing}>
                        {form.processing ? 'Saving…' : 'Save rules'}
                    </Button>
                </>
            }
        >
            <div className="grid gap-4">
                <Field label="Coverage type" error={form.errors.coverage_type}>
                    <Select
                        value={form.data.coverage_type}
                        onChange={(event) =>
                            form.setData(
                                'coverage_type',
                                event.currentTarget
                                    .value as InstitutionalCoverageType,
                            )
                        }
                    >
                        {configuration.coverageTypes.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Scheduled consultation limit / beneficiary"
                        error={form.errors.gp_limit_per_beneficiary}
                    >
                        <Input
                            type="number"
                            min="1"
                            value={form.data.gp_limit_per_beneficiary}
                            onChange={(event) =>
                                form.setData(
                                    'gp_limit_per_beneficiary',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                    <Field
                        label="Instant consultation limit / beneficiary"
                        error={form.errors.specialist_limit_per_beneficiary}
                    >
                        <Input
                            type="number"
                            min="1"
                            value={form.data.specialist_limit_per_beneficiary}
                            onChange={(event) =>
                                form.setData(
                                    'specialist_limit_per_beneficiary',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                </div>
                <Field
                    label="Daily usage limit"
                    error={form.errors.daily_consultation_limit}
                >
                    <Input
                        type="number"
                        min="1"
                        value={form.data.daily_consultation_limit}
                        onChange={(event) =>
                            form.setData(
                                'daily_consultation_limit',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field label="Expiry" error={form.errors.expiry_cadence}>
                    <Select
                        value={form.data.expiry_cadence}
                        onChange={(event) =>
                            form.setData(
                                'expiry_cadence',
                                event.currentTarget
                                    .value as InstitutionalCoverageExpiry,
                            )
                        }
                    >
                        {configuration.expiryCadences.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
                <Field
                    label="Payment preference"
                    error={form.errors.payment_preference}
                >
                    <Select
                        value={form.data.payment_preference}
                        onChange={(event) =>
                            form.setData(
                                'payment_preference',
                                event.currentTarget
                                    .value as InstitutionalPaymentPreference,
                            )
                        }
                    >
                        {configuration.paymentPreferences.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>
        </FundingDialog>
    );
}

export function ExtendProgramDialog({
    open,
    onOpenChange,
    program,
}: ControlledDialogProps & { program: InstitutionalFundingProgram }) {
    const form = useForm({ months: '12' });
    const projected = addMonthsNoOverflow(
        program.endsOn,
        Number(form.data.months),
    );

    function submit() {
        form.post(ExtendInstitutionalFundingProgramController.url(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
            onError: () => onOpenChange(true),
        });
    }

    return (
        <FundingDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Extend the program"
            description={`Currently ends ${date(program.endsOn)}.`}
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button onClick={submit} disabled={form.processing}>
                        {form.processing ? 'Extending…' : 'Extend'}
                    </Button>
                </>
            }
        >
            <div className="grid gap-4">
                <Field label="Extend by (months)" error={form.errors.months}>
                    <Input
                        type="number"
                        min="1"
                        max="60"
                        value={form.data.months}
                        onChange={(event) =>
                            form.setData('months', event.target.value)
                        }
                        className="focus-visible:border-success focus-visible:ring-success/20"
                    />
                </Field>
                <div className="flex gap-3 rounded-lg border border-border bg-muted/40 p-3 text-sm text-muted-foreground">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    <p>
                        Extending the program moves the coverage end date to{' '}
                        <strong className="font-medium text-foreground">
                            {date(projected)}
                        </strong>
                        . It does not add funds — top up the wallet separately.
                    </p>
                </div>
            </div>
        </FundingDialog>
    );
}

function FundingDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
}: ControlledDialogProps & {
    title: string;
    description: string;
    children: ReactNode;
    footer: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[520px]">
                <DialogHeader className="gap-1 border-b-0 pb-2">
                    <DialogTitle className="text-base font-semibold">
                        {title}
                    </DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="px-6 pb-5">{children}</div>
                <DialogFooter className="flex-row justify-between sm:justify-between">
                    {footer}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-1.5 text-sm font-medium">
            {label}
            {children}
            {error && <span className="text-xs text-destructive">{error}</span>}
        </label>
    );
}

function formatNumericInput(value: string): string {
    const [integer = '', decimal] = value.replace(/[^\d.]/g, '').split('.');
    const formatted =
        integer === '' ? '' : Number(integer).toLocaleString('en-NG');

    return decimal === undefined
        ? formatted
        : `${formatted}.${decimal.slice(0, 2)}`;
}

function money(value: number | string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function date(value: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${value}T00:00:00Z`));
}

function addMonthsNoOverflow(value: string, months: number): string {
    const original = new Date(`${value}T00:00:00Z`);
    const day = original.getUTCDate();
    const target = new Date(
        Date.UTC(original.getUTCFullYear(), original.getUTCMonth() + months, 1),
    );
    const lastDay = new Date(
        Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0),
    ).getUTCDate();
    target.setUTCDate(Math.min(day, lastDay));

    return target.toISOString().slice(0, 10);
}
