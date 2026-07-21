import { PlusIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

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

export type MedicalAccessFormData = {
    beneficiary: string;
    dataType: string;
    reason: string;
};

const beneficiaries = ['Ngozi Okafor', 'Chidi Okafor', 'Jane Okafor'];
const dataTypes = [
    'Clinical diagnosis & case notes',
    'Prescription records',
    'Laboratory results',
];

export function RequestAccessDialog({
    onSubmit,
}: {
    onSubmit: (data: MedicalAccessFormData) => void;
}) {
    const [open, setOpen] = useState(false);
    const [beneficiary, setBeneficiary] = useState<string | null>(null);
    const [dataType, setDataType] = useState<string | null>(null);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!beneficiary || !dataType) {
            return;
        }

        const form = new FormData(event.currentTarget);
        onSubmit({
            beneficiary,
            dataType,
            reason: String(form.get('reason')),
        });
        setBeneficiary(null);
        setDataType(null);
        setOpen(false);
    }

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
                Request Access
            </DialogTrigger>

            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Request medical access
                    </DialogTitle>
                    <DialogDescription className="max-w-[390px] leading-5">
                        A consent request is sent to the beneficiary&apos;s
                        Patient app. They decide whether to approve.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit}>
                    <div className="grid gap-4 px-6 py-4">
                        <SelectField
                            label="Beneficiary"
                            placeholder="Select beneficiary"
                            value={beneficiary}
                            onValueChange={setBeneficiary}
                            options={beneficiaries}
                        />
                        <SelectField
                            label="Data Type"
                            placeholder="Select data type"
                            value={dataType}
                            onValueChange={setDataType}
                            options={dataTypes}
                        />
                        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
                            Reason (shared with beneficiary)
                            <Textarea
                                name="reason"
                                required
                                placeholder="e.g. coordinating pharmacy refill"
                                className="min-h-[116px]"
                            />
                        </label>
                    </div>

                    <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                        <DialogClose
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
                        <Button type="submit" size="compact">
                            Send Request
                        </Button>
                    </DialogFooter>
                </form>
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
}: {
    label: string;
    placeholder: string;
    value: string | null;
    onValueChange: (value: string | null) => void;
    options: string[];
}) {
    return (
        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
            {label}
            <Select value={value} onValueChange={onValueChange} required>
                <SelectTrigger className="h-10 w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option} value={option}>
                            {option}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </label>
    );
}
