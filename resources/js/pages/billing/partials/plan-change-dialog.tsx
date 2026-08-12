import { Form } from '@inertiajs/react';
import { ArrowDownIcon, ArrowUpIcon, CreditCardIcon } from 'lucide-react';

import { store as storePlanChange } from '@/actions/App/Http/Controllers/Payments/StorePlanChangeController';
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
import type { Plan } from '@/types';

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

export function PlanChangeDialog({
    subscriptionId,
    plan,
    open,
    onOpenChange,
}: {
    subscriptionId: number;
    plan: Plan | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const change = plan?.plan_change;
    const isUpgrade = change?.direction === 'upgrade';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-lg">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="flex items-center gap-2 text-base leading-6 font-semibold">
                        {isUpgrade ? (
                            <ArrowUpIcon className="size-4 text-success" />
                        ) : (
                            <ArrowDownIcon className="size-4 text-warning" />
                        )}
                        {isUpgrade ? 'Upgrade' : 'Downgrade'} to {plan?.name}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {isUpgrade
                            ? 'The prorated difference is charged now and the new plan starts after verified payment.'
                            : 'No charge is made now. The new price is charged and the plan changes at your next billing cycle.'}
                    </DialogDescription>
                </DialogHeader>

                {plan && change && (
                    <Form
                        {...storePlanChange.form({
                            subscription: subscriptionId,
                            plan: plan.slug,
                        })}
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-5 px-6 py-5">
                                    <section
                                        className="grid gap-3 rounded-lg border bg-muted/30 p-4"
                                        aria-label="Plan change summary"
                                    >
                                        <SummaryRow
                                            label={
                                                isUpgrade
                                                    ? 'Prorated charge now'
                                                    : 'Charge now'
                                            }
                                            value={formatMoney(
                                                change.amount_due_now,
                                                plan.currency,
                                            )}
                                        />
                                        <SummaryRow
                                            label="New recurring amount"
                                            value={formatMoney(
                                                change.renewal_amount,
                                                plan.currency,
                                            )}
                                        />
                                        <SummaryRow
                                            label="Effective date"
                                            value={
                                                isUpgrade
                                                    ? 'Immediately after payment'
                                                    : dateFormatter.format(
                                                          new Date(
                                                              change.effective_at,
                                                          ),
                                                      )
                                            }
                                        />
                                    </section>

                                    <label className="flex items-start gap-3 rounded-lg border p-4 text-sm leading-5">
                                        <input
                                            type="checkbox"
                                            name="confirmed"
                                            value="1"
                                            required
                                            disabled={processing}
                                            className="mt-0.5 size-4 shrink-0 accent-primary"
                                        />
                                        <span>
                                            I confirm this plan change and its
                                            displayed billing date and amount.
                                        </span>
                                    </label>

                                    {(errors.confirmed ||
                                        errors.plan_change) && (
                                        <p
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors.confirmed ??
                                                errors.plan_change}
                                        </p>
                                    )}

                                    {isUpgrade && (
                                        <p className="flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                                            <CreditCardIcon className="mt-0.5 size-4 shrink-0" />
                                            You will continue to Paystack. The
                                            plan changes only after server-side
                                            payment verification.
                                        </p>
                                    )}
                                </div>

                                <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                                    <DialogClose
                                        disabled={processing}
                                        render={
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="compact"
                                            />
                                        }
                                    >
                                        Cancel
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        size="compact"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Processing…'
                                            : isUpgrade
                                              ? 'Continue to Paystack'
                                              : 'Schedule downgrade'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}
