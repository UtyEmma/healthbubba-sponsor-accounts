import { useForm } from '@inertiajs/react';
import type { InertiaFormProps } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Info,
    Store,
    Upload,
    UserRoundPlus,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import InputError from '@/components/input/input-error';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import campaigns from '@/routes/campaigns';
import type {
    CampaignCreationConfiguration,
    CampaignEnrollmentMethod,
} from '@/types';

interface CampaignFormData {
    allocation: string;
    launch: string;
    name: string;
    description: string;
    locations: string;
    start_date: string;
    end_date: string;
    enrollment_method: CampaignEnrollmentMethod;
    estimated_beneficiaries: string;
    gp_units: string;
    specialist_units: string;
    medication_budget: string;
    laboratory_budget: string;
    booth_required: boolean;
    booth_count: string;
    booth_preferred_deployment_date: string;
    booth_site: string;
    booth_contact_name: string;
    booth_contact_phone: string;
}

const initialData: CampaignFormData = {
    allocation: '',
    launch: '',
    name: '',
    description: '',
    locations: '',
    start_date: '',
    end_date: '',
    enrollment_method: 'upload',
    estimated_beneficiaries: '',
    gp_units: '0',
    specialist_units: '0',
    medication_budget: '0',
    laboratory_budget: '0',
    booth_required: false,
    booth_count: '1',
    booth_preferred_deployment_date: '',
    booth_site: '',
    booth_contact_name: '',
    booth_contact_phone: '',
};

const steps = [
    'Campaign details',
    'Beneficiaries',
    'Healthcare allocation',
    'Health Bubba Booth',
    'Summary',
] as const;

export function CreateCampaignDialog({
    open,
    onOpenChange,
    configuration,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    configuration: CampaignCreationConfiguration;
}) {
    const [step, setStep] = useState(1);
    const form = useForm<CampaignFormData>(initialData);
    const totals = useMemo(
        () => calculateTotals(form.data, configuration),
        [form.data, configuration],
    );

    function resetAndClose(): void {
        form.reset();
        form.clearErrors();
        setStep(1);
        onOpenChange(false);
    }

    function continueToNextStep(): void {
        form.clearErrors();

        if (!validateStep(step, form.data, form.setError)) {
            return;
        }

        setStep((current) => Math.min(5, current + 1));
    }

    function launch(): void {
        form.clearErrors();

        for (let position = 1; position <= 4; position++) {
            if (!validateStep(position, form.data, form.setError)) {
                setStep(position);

                return;
            }
        }

        form.post(campaigns.store().url, {
            preserveScroll: true,
            onError: (errors) => setStep(stepForErrors(Object.keys(errors))),
            onSuccess: resetAndClose,
        });
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen && !form.processing) {
                    resetAndClose();
                }
            }}
        >
            <DialogContent
                showCloseButton={false}
                overlayClassName="bg-black/50"
                className="max-h-[calc(100dvh-2rem)] max-w-[calc(100%-4rem)] overflow-y-auto rounded-xl bg-white shadow-2xl ring-1 ring-black/10 sm:max-w-4xl"
            >
                <DialogHeader className="gap-1 border-0 px-5 pt-5 pb-0 sm:px-6 sm:pt-6">
                    <DialogTitle className="text-xl leading-6 font-semibold text-foreground">
                        Start a campaign
                    </DialogTitle>
                    <DialogDescription className="text-[13px]">
                        Step {step} of 5 — {steps[step - 1]}
                    </DialogDescription>
                    <WizardProgress step={step} />
                </DialogHeader>

                <div className="px-5 pt-4 pb-5 sm:px-6">
                    {step === 1 && <DetailsStep data={form.data} form={form} />}
                    {step === 2 && (
                        <BeneficiariesStep data={form.data} form={form} />
                    )}
                    {step === 3 && (
                        <AllocationStep
                            data={form.data}
                            form={form}
                            configuration={configuration}
                            total={totals.healthcare}
                        />
                    )}
                    {step === 4 && (
                        <BoothStep
                            data={form.data}
                            form={form}
                            configuration={configuration}
                            totals={totals}
                        />
                    )}
                    {step === 5 && (
                        <SummaryStep
                            data={form.data}
                            configuration={configuration}
                            totals={totals}
                            error={form.errors.allocation}
                            launchError={form.errors.launch}
                        />
                    )}
                </div>

                <DialogFooter className="flex-col-reverse justify-between border-t px-5 py-4 sm:flex-row sm:px-6">
                    {step === 1 ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={resetAndClose}
                        >
                            Cancel
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setStep((current) => current - 1)}
                        >
                            <ChevronLeft className="size-4" />
                            Back
                        </Button>
                    )}

                    {step < 5 ? (
                        <Button type="button" onClick={continueToNextStep}>
                            <ChevronRight className="size-4" />
                            Continue
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            onClick={launch}
                            disabled={
                                form.processing ||
                                totals.launch >
                                    number(configuration.walletBalance)
                            }
                        >
                            {form.processing ? 'Launching…' : 'Launch campaign'}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function WizardProgress({ step }: { step: number }) {
    return (
        <div className="grid grid-cols-5 gap-1.5 pt-3">
            {steps.map((label, index) => {
                const position = index + 1;

                return (
                    <div key={label} className="min-w-0">
                        <div
                            className={cn(
                                'h-1 rounded-full bg-muted',
                                position < step && 'bg-emerald-700',
                                position === step && 'bg-[#35b847]',
                            )}
                        />
                        <div
                            className={cn(
                                'mt-2 truncate text-sm text-muted-foreground',
                                position === step && 'text-foreground',
                            )}
                        >
                            {label}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

type CampaignForm = InertiaFormProps<CampaignFormData>;

function DetailsStep({
    data,
    form,
}: {
    data: CampaignFormData;
    form: CampaignForm;
}) {
    return (
        <div className="space-y-4">
            <FormField label="Campaign name" error={form.errors.name}>
                <Input
                    value={data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    placeholder="Community D — Bompai"
                    autoFocus
                />
            </FormField>
            <FormField label="Description" error={form.errors.description}>
                <Textarea
                    className="min-h-14 resize-none"
                    value={data.description}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                    placeholder="Household primary care and screening across Bompai ward, Kano."
                />
            </FormField>
            <FormField
                label="Location"
                error={form.errors.locations}
                hint="Separate multiple communities with commas."
            >
                <Input
                    value={data.locations}
                    onChange={(event) =>
                        form.setData('locations', event.target.value)
                    }
                    placeholder="Bompai"
                />
            </FormField>
            <div className="grid gap-3 sm:grid-cols-2">
                <FormField label="Start date" error={form.errors.start_date}>
                    <Input
                        type="date"
                        value={data.start_date}
                        onChange={(event) =>
                            form.setData('start_date', event.target.value)
                        }
                    />
                </FormField>
                <FormField label="End date" error={form.errors.end_date}>
                    <Input
                        type="date"
                        value={data.end_date}
                        min={data.start_date}
                        onChange={(event) =>
                            form.setData('end_date', event.target.value)
                        }
                    />
                </FormField>
            </div>
        </div>
    );
}

function BeneficiariesStep({
    data,
    form,
}: {
    data: CampaignFormData;
    form: CampaignForm;
}) {
    return (
        <div className="space-y-4">
            <div>
                <h3 className="text-sm font-medium">
                    How will beneficiaries be added?
                </h3>
                <p className="mt-1 text-[13px] text-muted-foreground">
                    You can change this later — both channels stay open once the
                    campaign is live.
                </p>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <ChoiceCard
                    selected={data.enrollment_method === 'upload'}
                    onClick={() => form.setData('enrollment_method', 'upload')}
                    icon={<Upload className="size-5" />}
                    title="Upload beneficiary list"
                    description="Bulk import a spreadsheet of names and contacts"
                />
                <ChoiceCard
                    selected={data.enrollment_method === 'manual'}
                    onClick={() => form.setData('enrollment_method', 'manual')}
                    icon={<UserRoundPlus className="size-5" />}
                    title="Add beneficiaries manually"
                    description="Enter people one at a time from the campaign page"
                />
            </div>
            <FormField
                label="Estimated number of beneficiaries"
                error={form.errors.estimated_beneficiaries}
                hint="Used to size the allocation. It is not a hard cap."
            >
                <Input
                    type="number"
                    min="1"
                    value={data.estimated_beneficiaries}
                    onChange={(event) =>
                        form.setData(
                            'estimated_beneficiaries',
                            event.target.value,
                        )
                    }
                    placeholder="500"
                />
            </FormField>
            <InfoPanel>
                Adding a Health Bubba Booth in step 4 also unlocks on-site
                enrollment — booth officers register beneficiaries in person at
                the campaign location.
            </InfoPanel>
        </div>
    );
}

function AllocationStep({
    data,
    form,
    configuration,
    total,
}: {
    data: CampaignFormData;
    form: CampaignForm;
    configuration: CampaignCreationConfiguration;
    total: number;
}) {
    return (
        <div className="space-y-3">
            <div>
                <h3 className="text-sm font-medium">
                    Healthcare benefits for this campaign
                </h3>
                <p className="mt-1 text-[13px] leading-[18px] text-muted-foreground">
                    Consultations are allocated as priced units. Medication and
                    laboratory are allocated as a budget. This money is
                    reserved, not spent — it only leaves the wallet when a
                    beneficiary uses care.
                </p>
            </div>
            <AllocationCard
                title="Scheduled consultations"
                amount={number(data.gp_units) * number(configuration.gpUnitFee)}
                units={data.gp_units}
                price={configuration.gpUnitFee}
                onUnitsChange={(value) => form.setData('gp_units', value)}
            />
            <AllocationCard
                title="Instant consultations"
                amount={
                    number(data.specialist_units) *
                    number(configuration.specialistUnitFee)
                }
                units={data.specialist_units}
                price={configuration.specialistUnitFee}
                onUnitsChange={(value) =>
                    form.setData('specialist_units', value)
                }
            />
            <div className="grid gap-3 sm:grid-cols-2">
                <FormField
                    label="Medication budget (₦)"
                    error={form.errors.medication_budget}
                >
                    <Input
                        inputMode="decimal"
                        value={data.medication_budget}
                        onChange={(event) =>
                            form.setData(
                                'medication_budget',
                                event.target.value,
                            )
                        }
                    />
                </FormField>
                <FormField
                    label="Laboratory budget (₦)"
                    error={form.errors.laboratory_budget}
                >
                    <Input
                        inputMode="decimal"
                        value={data.laboratory_budget}
                        onChange={(event) =>
                            form.setData(
                                'laboratory_budget',
                                event.target.value,
                            )
                        }
                    />
                </FormField>
            </div>
            <InputError error={form.errors.allocation} />
            <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-4 py-3">
                <span className="text-sm font-medium">
                    Total healthcare allocation
                </span>
                <span className="text-xl font-semibold">
                    {formatMoney(total)}
                </span>
            </div>
        </div>
    );
}

function BoothStep({
    data,
    form,
    configuration,
    totals,
}: {
    data: CampaignFormData;
    form: CampaignForm;
    configuration: CampaignCreationConfiguration;
    totals: CampaignTotals;
}) {
    return (
        <div className="space-y-3">
            <div>
                <h3 className="text-sm font-medium">
                    Would you like to add a Health Bubba Booth to this campaign?
                </h3>
                <p className="mt-1 text-[13px] text-muted-foreground">
                    A booth is a staffed, branded physical access point at the
                    campaign location. It is entirely optional.
                </p>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <ChoiceCard
                    selected={!data.booth_required}
                    onClick={() => form.setData('booth_required', false)}
                    icon={<X className="size-6" />}
                    title="No booth"
                    description="Continue with the campaign setup"
                />
                <ChoiceCard
                    selected={data.booth_required}
                    onClick={() => form.setData('booth_required', true)}
                    icon={<Store className="size-5" />}
                    title="Yes, add a booth"
                    description="On-site enrollment and consultation support"
                />
            </div>

            {data.booth_required && (
                <>
                    <FeeFeatures
                        title="Booth Setup & Deployment Fee"
                        amount={`${formatMoney(number(configuration.boothSetupUnitFee))} per booth, one-time`}
                        features={[
                            'Branded Health Bubba booth',
                            'Tablet for consultations',
                            'Furniture & equipment',
                            'Power backup',
                            'Initial deployment',
                        ]}
                    />
                    <div className="grid gap-3 sm:grid-cols-2">
                        <FormField
                            label="Number of booths"
                            error={form.errors.booth_count}
                        >
                            <Input
                                type="number"
                                min="1"
                                max="100"
                                value={data.booth_count}
                                onChange={(event) =>
                                    form.setData(
                                        'booth_count',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                        <FormField
                            label="Preferred deployment date"
                            error={form.errors.booth_preferred_deployment_date}
                        >
                            <Input
                                type="date"
                                min={data.start_date}
                                max={data.end_date}
                                value={data.booth_preferred_deployment_date}
                                onChange={(event) =>
                                    form.setData(
                                        'booth_preferred_deployment_date',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                    </div>
                    <FormField
                        label="Booth site"
                        error={form.errors.booth_site}
                    >
                        <Input
                            value={data.booth_site}
                            onChange={(event) =>
                                form.setData('booth_site', event.target.value)
                            }
                            placeholder="Bompai Central Market"
                        />
                    </FormField>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <FormField
                            label="On-site contact"
                            error={form.errors.booth_contact_name}
                        >
                            <Input
                                value={data.booth_contact_name}
                                onChange={(event) =>
                                    form.setData(
                                        'booth_contact_name',
                                        event.target.value,
                                    )
                                }
                                placeholder="Tobi Adeyinka"
                            />
                        </FormField>
                        <FormField
                            label="Contact phone"
                            error={form.errors.booth_contact_phone}
                        >
                            <Input
                                type="tel"
                                value={data.booth_contact_phone}
                                onChange={(event) =>
                                    form.setData(
                                        'booth_contact_phone',
                                        event.target.value,
                                    )
                                }
                                placeholder="+234 802 555 0111"
                            />
                        </FormField>
                    </div>
                    <div className="rounded-lg border bg-muted/40 px-4 py-3">
                        <div className="flex justify-between gap-4 text-sm">
                            <span className="text-muted-foreground">
                                {number(data.booth_count)} booths ×{' '}
                                {formatMoney(
                                    number(configuration.boothSetupUnitFee),
                                )}
                            </span>
                            <strong>{formatMoney(totals.boothSetup)}</strong>
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Deducted from your wallet when the booth request is
                            confirmed. Available balance is{' '}
                            {formatMoney(number(configuration.walletBalance))}.
                        </p>
                    </div>
                    <FeeFeatures
                        information
                        title="Booth Management & Service Fee"
                        amount={`${formatMoney(number(configuration.boothMonthlyUnitFee))} per booth / month`}
                        features={[
                            '3 trained NYSC booth officers',
                            'Beneficiary enrollment support',
                            'Consultation assistance',
                            'Data & connectivity',
                            'Booth operations & supervision',
                            'Reporting',
                        ]}
                        footer={`${number(data.booth_count)} booths × ${formatMoney(number(configuration.boothMonthlyUnitFee))}`}
                        footerAmount={`${formatMoney(totals.boothMonthly)} / month`}
                        note="You are not charged for the whole campaign upfront. The service fee is deducted monthly from your wallet for each active booth, starting on the date Health Bubba confirms that booth is deployed and operational."
                    />
                </>
            )}
        </div>
    );
}

function SummaryStep({
    data,
    configuration,
    totals,
    error,
    launchError,
}: {
    data: CampaignFormData;
    configuration: CampaignCreationConfiguration;
    totals: CampaignTotals;
    error?: string;
    launchError?: string;
}) {
    const afterLaunch = number(configuration.walletBalance) - totals.launch;

    return (
        <div className="space-y-3">
            <div className="rounded-lg border px-4 py-3">
                <h3 className="font-semibold">{data.name}</h3>
                <p className="mt-1 text-sm text-muted-foreground">
                    {data.locations} · {data.estimated_beneficiaries} estimated
                    beneficiaries ·{' '}
                    {data.enrollment_method === 'upload'
                        ? 'list upload'
                        : 'manual entry'}
                    {data.booth_required ? ' · on-site booth enrollment' : ''}
                </p>
            </div>
            <SummarySection title="Healthcare allocation">
                <SummaryRow
                    label={`Scheduled consultations × ${number(data.gp_units).toLocaleString('en-NG')}`}
                    value={formatMoney(totals.gp)}
                />
                <SummaryRow
                    label={`Instant consultations × ${number(data.specialist_units).toLocaleString('en-NG')}`}
                    value={formatMoney(totals.specialist)}
                />
                <SummaryRow
                    label="Medication budget"
                    value={formatMoney(number(data.medication_budget))}
                />
                <SummaryRow
                    label="Laboratory budget"
                    value={formatMoney(number(data.laboratory_budget))}
                />
                <SummaryRow
                    label="Total healthcare allocation"
                    value={formatMoney(totals.healthcare)}
                    strong
                    shaded
                />
            </SummarySection>

            {data.booth_required && (
                <SummarySection title="Health Bubba Booth">
                    <SummaryRow
                        label="Number of booths"
                        value={number(data.booth_count).toLocaleString('en-NG')}
                    />
                    <SummaryRow
                        label="One-time booth setup cost"
                        value={formatMoney(totals.boothSetup)}
                    />
                    <SummaryRow
                        label="Monthly booth service cost"
                        value={`${formatMoney(totals.boothMonthly)} / month`}
                    />
                </SummarySection>
            )}

            <div className="rounded-xl border border-[#31b643] bg-[#f4fbf4] px-4 py-3">
                <div className="flex items-center justify-between gap-4 border-b pb-2">
                    <span className="text-sm font-medium">
                        Amount required to launch
                    </span>
                    <strong className="text-2xl">
                        {formatMoney(totals.launch)}
                    </strong>
                </div>
                <p className="border-b py-2 text-xs text-muted-foreground">
                    {formatMoney(totals.healthcare)} healthcare allocation
                    {data.booth_required
                        ? ` + ${formatMoney(totals.boothSetup)} booth setup`
                        : ''}
                </p>
                <div className="space-y-2 pt-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Available balance
                        </span>
                        <span>
                            {formatMoney(number(configuration.walletBalance))}
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Available after launching
                        </span>
                        <strong
                            className={cn(
                                'text-success',
                                afterLaunch < 0 && 'text-destructive',
                            )}
                        >
                            {formatMoney(afterLaunch)}
                        </strong>
                    </div>
                </div>
            </div>
            {data.booth_required && (
                <InfoPanel information>
                    The {formatMoney(totals.boothMonthly)} monthly service fee
                    is not part of the amount above. It is a recurring
                    operational charge deducted from your wallet each month
                    while a booth stays active. Consultation, medication and
                    laboratory allocations fund the care beneficiaries use;
                    booth fees cover the physical access point and the team
                    running it.
                </InfoPanel>
            )}
            {totals.launch > number(configuration.walletBalance) && (
                <InputError error="Your wallet does not have enough available balance to launch this campaign." />
            )}
            <InputError error={error} />
            <InputError error={launchError} />
        </div>
    );
}

function AllocationCard({
    title,
    amount,
    units,
    price,
    onUnitsChange,
}: {
    title: string;
    amount: number;
    units: string;
    price: string;
    onUnitsChange: (value: string) => void;
}) {
    return (
        <div className="rounded-lg border px-3 py-3">
            <div className="mb-3 flex items-center justify-between text-sm">
                <span>{title}</span>
                <strong>{formatMoney(amount)}</strong>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <FormField label="Units">
                    <Input
                        type="number"
                        min="0"
                        value={units}
                        onChange={(event) => onUnitsChange(event.target.value)}
                    />
                </FormField>
                <FormField label="Price each (₦)">
                    <Input value={integerValue(price)} readOnly />
                </FormField>
            </div>
        </div>
    );
}

function ChoiceCard({
    selected,
    onClick,
    icon,
    title,
    description,
}: {
    selected: boolean;
    onClick: () => void;
    icon: React.ReactNode;
    title: string;
    description: string;
}) {
    return (
        <button
            type="button"
            aria-pressed={selected}
            onClick={onClick}
            className={cn(
                'flex min-h-20 items-center gap-3 rounded-xl border bg-white p-3 text-left transition-colors',
                selected &&
                    'border-[#2fbd43] bg-[#f4fbf4] ring-1 ring-[#2fbd43]',
            )}
        >
            <span
                className={cn(
                    'grid size-10 shrink-0 place-items-center rounded-lg bg-muted text-muted-foreground',
                    selected && 'bg-primary text-primary-foreground',
                )}
            >
                {icon}
            </span>
            <span>
                <span className="block text-sm font-semibold">{title}</span>
                <span className="mt-0.5 block text-xs leading-4 text-muted-foreground">
                    {description}
                </span>
            </span>
        </button>
    );
}

function FeeFeatures({
    title,
    amount,
    features,
    information = false,
    footer,
    footerAmount,
    note,
}: {
    title: string;
    amount: string;
    features: string[];
    information?: boolean;
    footer?: string;
    footerAmount?: string;
    note?: string;
}) {
    return (
        <div
            className={cn(
                'rounded-xl border px-4 py-3',
                information && 'border-information/30 bg-information/5',
            )}
        >
            <div className="flex flex-wrap justify-between gap-2 text-sm">
                <strong>{title}</strong>
                <span>{amount}</span>
            </div>
            <div className="mt-3 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                {features.map((feature) => (
                    <div
                        key={feature}
                        className="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span
                            className={cn(
                                'text-success',
                                information && 'text-information',
                            )}
                        >
                            ✓
                        </span>
                        {feature}
                    </div>
                ))}
            </div>
            {footer && (
                <div className="mt-3 flex justify-between border-t pt-3 text-sm">
                    <span className="text-muted-foreground">{footer}</span>
                    <strong>{footerAmount}</strong>
                </div>
            )}
            {note && (
                <p className="mt-2 text-xs leading-[17px] text-muted-foreground">
                    {note}
                </p>
            )}
        </div>
    );
}

function SummarySection({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section>
            <h3 className="mb-3 text-sm font-medium">{title}</h3>
            <div className="overflow-hidden rounded-xl border">{children}</div>
        </section>
    );
}

function SummaryRow({
    label,
    value,
    strong = false,
    shaded = false,
}: {
    label: string;
    value: string;
    strong?: boolean;
    shaded?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-4 border-b px-4 py-3 text-sm last:border-b-0',
                shaded && 'bg-muted/40',
            )}
        >
            <span
                className={cn(
                    'text-muted-foreground',
                    strong && 'text-foreground',
                )}
            >
                {label}
            </span>
            <span className={cn(strong && 'text-base font-semibold')}>
                {value}
            </span>
        </div>
    );
}

function InfoPanel({
    children,
    information = false,
}: {
    children: React.ReactNode;
    information?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex gap-3 rounded-xl border bg-muted/40 px-3 py-3 text-[13px] leading-[18px] text-muted-foreground',
                information &&
                    'border-information/30 bg-information/5 text-information',
            )}
        >
            <Info className="mt-0.5 size-4 shrink-0" />
            <p>{children}</p>
        </div>
    );
}

function FormField({
    label,
    error,
    hint,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[13px] font-medium">{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError error={error} />
        </div>
    );
}

interface CampaignTotals {
    gp: number;
    specialist: number;
    healthcare: number;
    boothSetup: number;
    boothMonthly: number;
    launch: number;
}

function calculateTotals(
    data: CampaignFormData,
    configuration: CampaignCreationConfiguration,
): CampaignTotals {
    const gp = number(data.gp_units) * number(configuration.gpUnitFee);
    const specialist =
        number(data.specialist_units) * number(configuration.specialistUnitFee);
    const healthcare =
        gp +
        specialist +
        number(data.medication_budget) +
        number(data.laboratory_budget);
    const boothSetup = data.booth_required
        ? number(data.booth_count) * number(configuration.boothSetupUnitFee)
        : 0;
    const boothMonthly = data.booth_required
        ? number(data.booth_count) * number(configuration.boothMonthlyUnitFee)
        : 0;

    return {
        gp,
        specialist,
        healthcare,
        boothSetup,
        boothMonthly,
        launch: healthcare + boothSetup,
    };
}

function validateStep(
    step: number,
    data: CampaignFormData,
    setError: (field: keyof CampaignFormData, value: string) => void,
): boolean {
    let valid = true;
    const required = (field: keyof CampaignFormData, message: string): void => {
        if (String(data[field]).trim() === '') {
            setError(field, message);
            valid = false;
        }
    };

    if (step === 1) {
        required('name', 'Enter a campaign name.');
        required('description', 'Enter a campaign description.');
        required('locations', 'Enter at least one location.');
        required('start_date', 'Choose a start date.');
        required('end_date', 'Choose an end date.');

        if (
            data.start_date &&
            data.end_date &&
            data.end_date < data.start_date
        ) {
            setError(
                'end_date',
                'The end date must be on or after the start date.',
            );
            valid = false;
        }
    }

    if (step === 2 && number(data.estimated_beneficiaries) < 1) {
        setError(
            'estimated_beneficiaries',
            'Enter an estimated number of beneficiaries.',
        );
        valid = false;
    }

    if (step === 3) {
        const healthcare =
            number(data.gp_units) +
            number(data.specialist_units) +
            number(data.medication_budget) +
            number(data.laboratory_budget);

        if (healthcare <= 0) {
            setError(
                'allocation',
                'Add at least one healthcare benefit allocation.',
            );
            valid = false;
        }
    }

    if (step === 4 && data.booth_required) {
        required('booth_count', 'Enter the number of booths.');
        required(
            'booth_preferred_deployment_date',
            'Choose a preferred deployment date.',
        );
        required('booth_site', 'Enter the booth site.');
        required('booth_contact_name', 'Enter an on-site contact.');
        required('booth_contact_phone', 'Enter a contact phone number.');
    }

    return valid;
}

function stepForErrors(fields: string[]): number {
    if (
        fields.some((field) =>
            [
                'name',
                'description',
                'locations',
                'start_date',
                'end_date',
            ].includes(field),
        )
    ) {
        return 1;
    }

    if (
        fields.some((field) =>
            ['enrollment_method', 'estimated_beneficiaries'].includes(field),
        )
    ) {
        return 2;
    }

    if (
        fields.some((field) =>
            [
                'gp_units',
                'specialist_units',
                'medication_budget',
                'laboratory_budget',
                'allocation',
            ].includes(field),
        )
    ) {
        return 3;
    }

    if (fields.some((field) => field.startsWith('booth_'))) {
        return 4;
    }

    return 5;
}

function number(value: string | number | null | undefined): number {
    const parsed = Number(String(value ?? 0).replaceAll(',', ''));

    return Number.isFinite(parsed) ? parsed : 0;
}

function integerValue(value: string): string {
    return number(value).toFixed(0);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(value);
}
