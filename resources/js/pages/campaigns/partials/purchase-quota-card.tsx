import { useForm } from '@inertiajs/react';
import { useState } from 'react';

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
import { Label } from '@/components/ui/label';
import campaigns from '@/routes/campaigns';
import type { Campaign } from '@/types';

const formatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
});

interface PurchaseQuotaForm {
    consultation_type: string;
    quantity: string;
    purchase?: string;
}

export function PurchaseQuotaCard({ campaign }: { campaign: Campaign }) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } =
        useForm<PurchaseQuotaForm>({
            consultation_type: '',
            quantity: '',
        });

    const gpFee = campaign.gpFee ? parseFloat(campaign.gpFee) : 0;
    const specialistFee = campaign.specialistFee
        ? parseFloat(campaign.specialistFee)
        : 0;

    const unitFee =
        data.consultation_type === 'gp'
            ? gpFee
            : data.consultation_type === 'specialist'
              ? specialistFee
              : 0;
    const quantity = parseInt(data.quantity, 10) || 0;
    const totalCost = unitFee * quantity;

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(campaigns.consultationQuotas.store(campaign.slug).url, {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={<Button size="compact" />}>
                Top up
            </DialogTrigger>

            <DialogContent showCloseButton={false}>
                <DialogHeader className="border-b px-6 pt-6 pb-4">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Top up coverage
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Purchase additional consultation units for the pool.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit}>
                    <div className="grid gap-4 px-6 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="consultation_type">
                                Consultation type
                            </Label>
                            <select
                                id="consultation_type"
                                name="consultation_type"
                                value={data.consultation_type}
                                onChange={(event) =>
                                    setData(
                                        'consultation_type',
                                        event.target.value,
                                    )
                                }
                                className="h-10 w-full rounded-control border border-input bg-background px-3 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/20"
                            >
                                <option value="">Select type</option>
                                <option value="gp">
                                    GP Consultation
                                    {gpFee > 0
                                        ? ` (${formatter.format(gpFee)}/unit)`
                                        : ''}
                                </option>
                                <option value="specialist">
                                    Specialist Consultation
                                    {specialistFee > 0
                                        ? ` (${formatter.format(specialistFee)}/unit)`
                                        : ''}
                                </option>
                            </select>
                            {errors.consultation_type && (
                                <p className="text-xs text-destructive">
                                    {errors.consultation_type}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="quantity">
                                Number of consultations
                            </Label>
                            <Input
                                id="quantity"
                                type="number"
                                min={1}
                                max={1000}
                                value={data.quantity}
                                onChange={(e) =>
                                    setData('quantity', e.target.value)
                                }
                                placeholder="e.g. 10"
                            />
                            {errors.quantity && (
                                <p className="text-xs text-destructive">
                                    {errors.quantity}
                                </p>
                            )}
                        </div>

                        {unitFee > 0 && quantity > 0 && (
                            <div className="rounded-lg border border-border bg-muted/50 p-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Unit fee
                                    </span>
                                    <span>{formatter.format(unitFee)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Quantity
                                    </span>
                                    <span>{quantity}</span>
                                </div>
                                <div className="mt-2 flex justify-between border-t pt-2 font-semibold">
                                    <span>Total cost</span>
                                    <span>{formatter.format(totalCost)}</span>
                                </div>
                            </div>
                        )}
                        {errors.purchase && (
                            <p
                                className="text-sm text-destructive"
                                role="alert"
                            >
                                {errors.purchase}
                            </p>
                        )}
                    </div>

                    <DialogFooter className="flex-row justify-end gap-3 border-t px-6 py-3">
                        <DialogClose
                            render={
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="compact"
                                    disabled={processing}
                                />
                            }
                        >
                            Cancel
                        </DialogClose>
                        <Button
                            type="submit"
                            size="compact"
                            disabled={
                                processing ||
                                !data.consultation_type ||
                                !data.quantity
                            }
                        >
                            {processing ? 'Processing…' : 'Purchase quota'}
                        </Button>
                    </DialogFooter>
                    {wasSuccessful && (
                        <p className="px-6 pb-4 text-sm text-success">
                            Consultation quota purchased successfully.
                        </p>
                    )}
                </form>
            </DialogContent>
        </Dialog>
    );
}
