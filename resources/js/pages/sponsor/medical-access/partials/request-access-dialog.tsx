import { Form } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useState } from 'react';

import StoreMedicalAccessRequestController from '@/actions/App/Http/Controllers/MedicalAccessRequests/StoreMedicalAccessRequestController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
    MedicalAccessBeneficiary,
    MedicalAccessDataTypeOption,
} from '@/types';

export function RequestAccessDialog({
    beneficiaries,
    dataTypes,
}: {
    beneficiaries: MedicalAccessBeneficiary[];
    dataTypes: MedicalAccessDataTypeOption[];
}) {
    const [open, setOpen] = useState(false);
    const [beneficiary, setBeneficiary] = useState<string | null>(null);
    const [dataType, setDataType] = useState<string | null>(null);

    function close(): void {
        setOpen(false);
        setBeneficiary(null);
        setDataType(null);
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (!nextOpen) {
                    setBeneficiary(null);
                    setDataType(null);
                }
            }}
        >
            <DialogTrigger
                render={
                    <Button
                        size="compact"
                        className="self-start sm:self-auto"
                        disabled={beneficiaries.length === 0}
                    />
                }
            >
                <PlusIcon className="size-4" />
                Request Access
            </DialogTrigger>

            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Request medical access
                    </DialogTitle>
                    <DialogDescription className="max-w-[390px] leading-5">
                        The beneficiary will receive an email with a secure link
                        to allow or deny this request within 24 hours.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...StoreMedicalAccessRequestController.form()}
                    resetOnSuccess
                    onSuccess={close}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 px-6 py-4">
                                <input
                                    type="hidden"
                                    name="beneficiary_public_id"
                                    value={beneficiary ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="data_type"
                                    value={dataType ?? ''}
                                />
                                <SelectField
                                    label="Beneficiary"
                                    placeholder="Select beneficiary"
                                    value={beneficiary}
                                    onValueChange={setBeneficiary}
                                    options={beneficiaries.map((item) => ({
                                        value: item.publicId,
                                        label: `${item.name} (${item.email})`,
                                    }))}
                                    error={errors.beneficiary_public_id}
                                    disabled={processing}
                                />
                                <SelectField
                                    label="Data type"
                                    placeholder="Select data type"
                                    value={dataType}
                                    onValueChange={setDataType}
                                    options={dataTypes}
                                    error={errors.data_type}
                                    disabled={processing}
                                />
                                <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
                                    Reason (optional, shared with beneficiary)
                                    <Textarea
                                        name="reason"
                                        maxLength={1000}
                                        placeholder="e.g. Coordinating a pharmacy refill"
                                        className="min-h-[116px]"
                                        aria-invalid={Boolean(errors.reason)}
                                        aria-describedby={
                                            errors.reason
                                                ? 'medical-access-reason-error'
                                                : undefined
                                        }
                                        disabled={processing}
                                    />
                                    {errors.reason && (
                                        <span
                                            id="medical-access-reason-error"
                                            className="text-sm font-normal text-destructive"
                                        >
                                            {errors.reason}
                                        </span>
                                    )}
                                </label>
                            </div>

                            <DialogFooter className="flex-row justify-end border-t px-6 py-4">
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
                                        processing || !beneficiary || !dataType
                                    }
                                >
                                    {processing ? 'Sending…' : 'Send Request'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function SelectField({
    label,
    placeholder,
    value,
    onValueChange,
    options,
    error,
    disabled,
}: {
    label: string;
    placeholder: string;
    value: string | null;
    onValueChange: (value: string | null) => void;
    options: Array<{ value: string; label: string }>;
    error?: string;
    disabled: boolean;
}) {
    const errorId = `${label.toLowerCase().replaceAll(' ', '-')}-error`;

    return (
        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
            {label}
            <Select
                value={value}
                onValueChange={onValueChange}
                disabled={disabled}
            >
                <SelectTrigger
                    className="h-10 w-full"
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? errorId : undefined}
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
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
