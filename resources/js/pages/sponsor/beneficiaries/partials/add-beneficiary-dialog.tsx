import { Form } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useState } from 'react';
import type { ComponentProps } from 'react';

import StoreWorkspaceBeneficiaryController from '@/actions/App/Http/Controllers/WorkspaceBeneficiaries/StoreWorkspaceBeneficiaryController';
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
import type { WorkspaceCapacity } from '@/types';

export function AddBeneficiaryDialog({
    form,
}: {
    form?: ReturnType<typeof StoreWorkspaceBeneficiaryController.form>;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger
                render={
                    <Button
                        size="compact"
                        className="self-start sm:self-auto"
                    />
                }
            >
                <PlusIcon className="size-4" />
                Add beneficiary
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="border-b px-6 pt-6 pb-4">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Add a beneficiary
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {/* The invitation reserves one of {capacity.remaining}{' '}
                        remaining beneficiary slots for 24 hours. */}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(form ?? StoreWorkspaceBeneficiaryController.form())}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 px-6 py-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormField
                                        label="First name"
                                        name="first_name"
                                        error={errors.first_name}
                                        disabled={processing}
                                    />
                                    <FormField
                                        label="Last name"
                                        name="last_name"
                                        error={errors.last_name}
                                        disabled={processing}
                                    />
                                </div>
                                <FormField
                                    label="Email address"
                                    name="email"
                                    type="email"
                                    error={errors.email}
                                    disabled={processing}
                                />
                                <FormField
                                    label="Phone number"
                                    name="phone"
                                    type="tel"
                                    error={errors.phone}
                                    disabled={processing}
                                />
                                {(errors.capacity ||
                                    errors.subscription ||
                                    errors.campaign) && (
                                    <p
                                        className="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {errors.capacity ??
                                            errors.subscription ??
                                            errors.campaign}
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
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Sending…'
                                        : 'Send invitation'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function FormField({
    label,
    name,
    error,
    ...props
}: { label: string; name: string; error?: string } & ComponentProps<
    typeof Input
>) {
    const errorId = `${name}-error`;

    return (
        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
            {label}
            <Input
                name={name}
                required
                aria-invalid={Boolean(error)}
                aria-describedby={error ? errorId : undefined}
                {...props}
            />
            {error && (
                <span
                    id={errorId}
                    className="text-sm font-normal text-destructive"
                >
                    {error}
                </span>
            )}
        </label>
    );
}
