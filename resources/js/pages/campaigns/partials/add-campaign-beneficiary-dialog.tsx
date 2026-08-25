import { Form } from '@inertiajs/react';
import { UploadCloudIcon, UserRoundPlusIcon } from 'lucide-react';
import { useId, useState } from 'react';
import type { ComponentProps } from 'react';

import ImportCampaignBeneficiariesController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/ImportCampaignBeneficiariesController';
import StoreCampaignBeneficiaryController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/StoreCampaignBeneficiaryController';
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
import { Progress } from '@/components/ui/progress';
import type { CampaignBeneficiaryCapacity } from '@/types';

type DialogStep = 'method' | 'manual' | 'upload';

export function AddCampaignBeneficiaryDialog({
    campaignSlug,
    capacity,
}: {
    campaignSlug: string;
    capacity: CampaignBeneficiaryCapacity;
}) {
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState<DialogStep>('method');

    function changeOpen(next: boolean) {
        setOpen(next);

        if (!next) {
            setStep('method');
        }
    }

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogTrigger
                render={
                    <Button
                        size="compact"
                        disabled={!capacity.canInvite}
                        className="self-start sm:self-auto"
                    />
                }
            >
                <UserRoundPlusIcon className="size-4" /> Add beneficiary
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                {step === 'method' && <MethodStep onSelect={setStep} />}
                {step === 'manual' && (
                    <ManualStep
                        campaignSlug={campaignSlug}
                        capacity={capacity}
                        onBack={() => setStep('method')}
                        onSuccess={() => changeOpen(false)}
                    />
                )}
                {step === 'upload' && (
                    <UploadStep
                        campaignSlug={campaignSlug}
                        onBack={() => setStep('method')}
                        onSuccess={() => changeOpen(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function MethodStep({
    onSelect,
}: {
    onSelect: (step: 'manual' | 'upload') => void;
}) {
    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base">
                    Add beneficiaries
                </DialogTitle>
                <DialogDescription>
                    Choose a manual invitation or a CSV/XLSX bulk import.
                </DialogDescription>
            </DialogHeader>
            <div className="grid gap-3 px-6 py-5 sm:grid-cols-2">
                <Button
                    type="button"
                    variant="outline"
                    className="h-auto justify-start p-4"
                    onClick={() => onSelect('manual')}
                >
                    <UserRoundPlusIcon className="size-5" /> Manual invitation
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    className="h-auto justify-start p-4"
                    onClick={() => onSelect('upload')}
                >
                    <UploadCloudIcon className="size-5" /> CSV or XLSX
                </Button>
            </div>
            <DialogFooter className="border-t px-6 py-4">
                <DialogClose
                    render={<Button variant="outline" size="compact" />}
                >
                    Cancel
                </DialogClose>
            </DialogFooter>
        </>
    );
}

function ManualStep({
    campaignSlug,
    capacity,
    onBack,
    onSuccess,
}: {
    campaignSlug: string;
    capacity: CampaignBeneficiaryCapacity;
    onBack: () => void;
    onSuccess: () => void;
}) {
    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base">
                    Invite a beneficiary
                </DialogTitle>
                <DialogDescription>
                    The invitation reserves one of {capacity.remaining}{' '}
                    remaining campaign spaces for 24 hours.
                </DialogDescription>
            </DialogHeader>
            <Form
                {...StoreCampaignBeneficiaryController.form(campaignSlug)}
                resetOnSuccess
                onSuccess={onSuccess}
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-4 px-6 py-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="First name"
                                    name="first_name"
                                    error={errors.first_name}
                                    disabled={processing}
                                />
                                <Field
                                    label="Last name"
                                    name="last_name"
                                    error={errors.last_name}
                                    disabled={processing}
                                />
                            </div>
                            <Field
                                label="Email address"
                                name="email"
                                type="email"
                                error={errors.email}
                                disabled={processing}
                            />
                            <Field
                                label="Phone number"
                                name="phone"
                                type="tel"
                                error={errors.phone}
                                disabled={processing}
                            />
                            {(errors.capacity || errors.campaign) && (
                                <p className="text-sm text-destructive">
                                    {errors.capacity ?? errors.campaign}
                                </p>
                            )}
                        </div>
                        <DialogFooter className="flex-row justify-end gap-2 border-t px-6 py-4">
                            <Button
                                type="button"
                                variant="outline"
                                size="compact"
                                onClick={onBack}
                                disabled={processing}
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                size="compact"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Inviting…'
                                    : 'Reserve space & invite'}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </Form>
        </>
    );
}

function UploadStep({
    campaignSlug,
    onBack,
    onSuccess,
}: {
    campaignSlug: string;
    onBack: () => void;
    onSuccess: () => void;
}) {
    const id = useId();
    const [fileName, setFileName] = useState<string | null>(null);

    return (
        <>
            <DialogHeader className="border-b px-6 py-5">
                <DialogTitle className="text-base">
                    Bulk upload beneficiaries
                </DialogTitle>
                <DialogDescription>
                    Use the required first_name, last_name, email, and phone
                    columns.
                </DialogDescription>
            </DialogHeader>
            <Form
                {...ImportCampaignBeneficiariesController.form(campaignSlug)}
                onSuccess={onSuccess}
            >
                {({ errors, processing, progress }) => (
                    <>
                        <div className="grid gap-3 px-6 py-5">
                            <label
                                htmlFor={id}
                                className="flex min-h-24 cursor-pointer items-center gap-3 rounded-2xl border bg-card px-4 shadow-control"
                            >
                                <span className="flex size-10 items-center justify-center rounded-full border">
                                    <UploadCloudIcon className="size-5" />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate font-medium">
                                        {fileName ??
                                            'Choose a CSV or XLSX file'}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        10 MB maximum · first worksheet only
                                    </span>
                                </span>
                                <span className="text-sm font-medium text-primary">
                                    Browse
                                </span>
                            </label>
                            <input
                                id={id}
                                name="file"
                                type="file"
                                accept=".csv,.xlsx"
                                required
                                className="sr-only"
                                onChange={(event) =>
                                    setFileName(
                                        event.target.files?.[0]?.name ?? null,
                                    )
                                }
                            />
                            {progress && (
                                <div>
                                    <Progress
                                        value={progress.percentage ?? 0}
                                    />
                                    <p className="pt-1 text-xs text-muted-foreground">
                                        Uploading {progress.percentage ?? 0}%
                                    </p>
                                </div>
                            )}
                            {errors.file && (
                                <p className="text-sm text-destructive">
                                    {errors.file}
                                </p>
                            )}
                        </div>
                        <DialogFooter className="flex-row justify-end gap-2 border-t px-6 py-4">
                            <Button
                                type="button"
                                variant="outline"
                                size="compact"
                                onClick={onBack}
                                disabled={processing}
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                size="compact"
                                disabled={processing || !fileName}
                            >
                                {processing
                                    ? 'Importing…'
                                    : 'Import beneficiaries'}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </Form>
        </>
    );
}

function Field({
    label,
    name,
    error,
    ...props
}: {
    label: string;
    name: string;
    error?: string;
} & ComponentProps<typeof Input>) {
    return (
        <label className="grid gap-1.5 text-[13px] font-medium">
            {label}
            <Input
                name={name}
                required
                aria-invalid={Boolean(error)}
                {...props}
            />
            {error && (
                <span className="text-sm font-normal text-destructive">
                    {error}
                </span>
            )}
        </label>
    );
}
