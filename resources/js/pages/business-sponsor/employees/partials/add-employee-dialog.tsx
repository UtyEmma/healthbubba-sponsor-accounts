import { UploadCloudIcon, UserRoundPlusIcon } from 'lucide-react';
import { useId, useState } from 'react';
import type { ChangeEvent, ComponentProps, FormEvent } from 'react';

import { Button, buttonVariants } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';

type ProvisioningMethod = 'manual' | 'csv';
type DialogStep = 'method' | ProvisioningMethod;

export function AddEmployeeDialog({
    onContinue,
}: {
    onContinue: (method: ProvisioningMethod) => void;
}) {
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<DialogStep>('method');
    const [method, setMethod] = useState<ProvisioningMethod>('csv');

    function changeOpen(nextOpen: boolean) {
        setOpen(nextOpen);

        if (!nextOpen) {
            setStep('method');
        }
    }

    function chooseMethod(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setStep(method);
    }

    function completeFlow(selectedMethod: ProvisioningMethod) {
        onContinue(selectedMethod);
        changeOpen(false);
    }

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger
                render={
                    <Button
                        size="compact"
                        className="self-start sm:self-auto"
                    />
                }
            >
                <UserRoundPlusIcon className="size-4" />
                Add Employee
            </DialogTrigger>

            <DialogContent showCloseButton={false}>
                {step === 'method' && (
                    <MethodStep
                        method={method}
                        onMethodChange={setMethod}
                        onSubmit={chooseMethod}
                    />
                )}
                {step === 'manual' && (
                    <ManualEmployeeStep
                        onComplete={() => completeFlow('manual')}
                    />
                )}
                {step === 'csv' && (
                    <BulkUploadStep
                        onBack={() => setStep('method')}
                        onComplete={() => completeFlow('csv')}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function MethodStep({
    method,
    onMethodChange,
    onSubmit,
}: {
    method: ProvisioningMethod;
    onMethodChange: (value: ProvisioningMethod) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
}) {
    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base leading-6 font-semibold">
                    Add Employee
                </DialogTitle>
                <DialogDescription className="sr-only">
                    Choose whether to add an employee manually or upload a CSV.
                </DialogDescription>
            </DialogHeader>

            <form onSubmit={onSubmit}>
                <fieldset className="grid gap-3 px-6 py-4">
                    <legend className="sr-only">
                        Choose how to add employees
                    </legend>
                    <ProvisioningChoice
                        value="manual"
                        selected={method === 'manual'}
                        title="Add employees manually"
                        description="Provide users name and email address and other details"
                        onChange={onMethodChange}
                    />
                    <ProvisioningChoice
                        value="csv"
                        selected={method === 'csv'}
                        title="Upload CSV"
                        description="Batch upload employees to your database"
                        onChange={onMethodChange}
                    />
                </fieldset>

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
                        Next
                    </Button>
                </DialogFooter>
            </form>
        </>
    );
}

function ManualEmployeeStep({ onComplete }: { onComplete: () => void }) {
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        onComplete();
    }

    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base leading-6 font-semibold">
                    Add an employee
                </DialogTitle>
                <DialogDescription className="leading-5">
                    A seat is reserved immediately. The employee activates
                    coverage via the Patient app.
                </DialogDescription>
            </DialogHeader>

            <form onSubmit={submit}>
                <div className="grid gap-4 px-6 py-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="employee-first-name"
                            label="First name"
                            placeholder="Enter first name"
                            autoComplete="given-name"
                        />
                        <FormField
                            id="employee-last-name"
                            label="Last name"
                            placeholder="Enter last name"
                            autoComplete="family-name"
                        />
                    </div>
                    <FormField
                        id="employee-department"
                        label="Department"
                        placeholder="Enter department"
                        autoComplete="organization-title"
                    />
                    <FormField
                        id="employee-email"
                        label="Email Address"
                        placeholder="Enter email address"
                        type="email"
                        autoComplete="email"
                    />
                    <FormField
                        id="employee-phone"
                        label="Phone number"
                        placeholder="Enter phone number"
                        type="tel"
                        autoComplete="tel"
                    />
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
                        Reserve seat &amp; invite
                    </Button>
                </DialogFooter>
            </form>
        </>
    );
}

function FormField({
    id,
    label,
    ...props
}: {
    id: string;
    label: string;
} & ComponentProps<typeof Input>) {
    return (
        <div className="grid gap-2">
            <label htmlFor={id} className="text-[13px] font-medium">
                {label}
            </label>
            <Input id={id} name={id} required {...props} />
        </div>
    );
}

function BulkUploadStep({
    onBack,
    onComplete,
}: {
    onBack: () => void;
    onComplete: () => void;
}) {
    const inputId = useId();
    const [file, setFile] = useState<File | null>(null);

    function selectFile(event: ChangeEvent<HTMLInputElement>) {
        setFile(event.target.files?.[0] ?? null);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        onComplete();
    }

    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base leading-6 font-semibold">
                    Bulk upload employees
                </DialogTitle>
                <DialogDescription className="leading-5">
                    Upload a .csv or .xlsx file (or paste rows). Valid rows are
                    committed immediately; invalid rows are skipped and listed
                    below
                </DialogDescription>
            </DialogHeader>

            <form onSubmit={submit}>
                <div className="grid gap-2 px-6 py-5">
                    <label
                        htmlFor={inputId}
                        className="text-[13px] text-muted-foreground"
                    >
                        Upload your document for parsing
                    </label>
                    <div className="flex min-h-[74px] items-center gap-3 rounded-2xl border bg-card px-4 shadow-control">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-full border">
                            <UploadCloudIcon className="size-5 text-muted-foreground" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate font-medium">
                                {file?.name ?? '[Name of file]'}
                            </span>
                            <span className="block pt-0.5 text-[13px] text-muted-foreground">
                                200mb max.
                            </span>
                        </span>
                        <label
                            htmlFor={inputId}
                            className={cn(
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'compact',
                                }),
                                'cursor-pointer',
                            )}
                        >
                            Upload
                        </label>
                        <input
                            id={inputId}
                            type="file"
                            accept=".csv,.xlsx"
                            className="sr-only"
                            onChange={selectFile}
                        />
                    </div>
                </div>

                <DialogFooter className="flex-row justify-between border-t px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        size="compact"
                        onClick={onBack}
                        className="mr-auto"
                    >
                        Back
                    </Button>
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
                    <Button type="submit" size="compact" disabled={!file}>
                        Confirm
                    </Button>
                </DialogFooter>
            </form>
        </>
    );
}

function ProvisioningChoice({
    value,
    selected,
    title,
    description,
    onChange,
}: {
    value: ProvisioningMethod;
    selected: boolean;
    title: string;
    description: string;
    onChange: (value: ProvisioningMethod) => void;
}) {
    return (
        <label
            className={cn(
                'flex min-h-[64px] cursor-pointer items-start gap-3 rounded-2xl border bg-card px-3 py-3 shadow-control transition-colors',
                selected && 'border-information ring-1 ring-information/10',
            )}
        >
            <input
                type="radio"
                name="provisioningMethod"
                value={value}
                checked={selected}
                onChange={() => onChange(value)}
                className="mt-0.5 size-4 accent-primary"
            />
            <span>
                <span className="block text-sm font-medium">{title}</span>
                <span className="block pt-0.5 text-[13px] leading-[18px] text-muted-foreground">
                    {description}
                </span>
            </span>
        </label>
    );
}
