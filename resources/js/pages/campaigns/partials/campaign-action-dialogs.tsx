import { useForm } from '@inertiajs/react';
import { Download, Info, TriangleAlert, Upload } from 'lucide-react';
import { useRef } from 'react';

import AddCampaignBoothsController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/AddCampaignBoothsController';
import AllocateMoreToCampaignController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/AllocateMoreToCampaignController';
import DownloadCampaignImportErrorsController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/DownloadCampaignImportErrorsController';
import EndCampaignController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/EndCampaignController';
import ImportCampaignBeneficiariesController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/ImportCampaignBeneficiariesController';
import RecordCampaignUsageController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/RecordCampaignUsageController';
import StoreCampaignBeneficiaryController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/StoreCampaignBeneficiaryController';
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
import { Textarea } from '@/components/ui/textarea';
import type {
    Campaign,
    CampaignDetail,
    EmployeeImportResult,
    WorkspaceBeneficiary,
} from '@/types';

const controlClass =
    'h-10 w-full rounded-control border border-input bg-background px-3 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/20';

interface DialogProps {
    campaign: Campaign;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="grid gap-1.5 text-sm font-medium">
            {label}
            {children}
            {error && (
                <span className="text-xs font-normal text-destructive">
                    {error}
                </span>
            )}
        </label>
    );
}

function ErrorSummary({
    errors,
}: {
    errors: Record<string, string | undefined>;
}) {
    const messages = [...new Set(Object.values(errors).filter(Boolean))];

    return messages.length > 0 ? (
        <div className="rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm text-destructive">
            {messages.join(' ')}
        </div>
    ) : null;
}

function Shell({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
    wide = false,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    children: React.ReactNode;
    footer: React.ReactNode;
    wide?: boolean;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                showCloseButton={false}
                className={wide ? 'sm:max-w-[672px]' : 'sm:max-w-[520px]'}
            >
                <DialogHeader className="gap-1 border-b-0 pb-2">
                    <DialogTitle className="text-base font-semibold">
                        {title}
                    </DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="max-h-[68vh] overflow-y-auto px-6 pb-5">
                    {children}
                </div>
                <DialogFooter className="justify-between sm:justify-between">
                    {footer}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function AllocateMoreDialog({
    campaign,
    detail,
    open,
    onOpenChange,
}: DialogProps & { detail: CampaignDetail }) {
    const form = useForm({
        gp_units: '0',
        specialist_units: '0',
        medication_budget: '0',
        laboratory_budget: '0',
    });
    const total =
        Number(form.data.gp_units || 0) *
            Number(detail.configuration.gpUnitFee) +
        Number(form.data.specialist_units || 0) *
            Number(detail.configuration.specialistUnitFee) +
        Number((form.data.medication_budget || '0').replaceAll(',', '')) +
        Number((form.data.laboratory_budget || '0').replaceAll(',', ''));
    const after = Number(detail.configuration.walletBalance) - total;
    const submit = () =>
        form.post(AllocateMoreToCampaignController.url(campaign.slug), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title={`Allocate more to ${campaign.name}`}
            description={`Reserve additional funds from your available balance of ${money(detail.configuration.walletBalance)}.`}
            wide
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        disabled={form.processing || total <= 0}
                        onClick={submit}
                    >
                        Allocate
                    </Button>
                </>
            }
        >
            <div className="space-y-3">
                {(
                    [
                        [
                            'GP consultations',
                            'gp_units',
                            detail.configuration.gpUnitFee,
                        ],
                        [
                            'Specialist consultations',
                            'specialist_units',
                            detail.configuration.specialistUnitFee,
                        ],
                    ] as const
                ).map(([label, field, price]) => (
                    <div key={field} className="rounded-xl border p-3">
                        <div className="mb-2 flex items-center justify-between text-sm font-medium">
                            <span>{label}</span>
                            <span>
                                {money(
                                    Number(form.data[field] || 0) *
                                        Number(price),
                                )}
                            </span>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Field label="Units" error={form.errors[field]}>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data[field]}
                                    onChange={(event) =>
                                        form.setData(field, event.target.value)
                                    }
                                />
                            </Field>
                            <Field label="Price each (₦)">
                                <Input
                                    value={price}
                                    readOnly
                                    className="bg-muted/40"
                                />
                            </Field>
                        </div>
                    </div>
                ))}
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                        label="Medication budget (₦)"
                        error={form.errors.medication_budget}
                    >
                        <Input
                            value={form.data.medication_budget}
                            onChange={(event) =>
                                form.setData(
                                    'medication_budget',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                    <Field
                        label="Laboratory budget (₦)"
                        error={form.errors.laboratory_budget}
                    >
                        <Input
                            value={form.data.laboratory_budget}
                            onChange={(event) =>
                                form.setData(
                                    'laboratory_budget',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>
                </div>
                <div className="rounded-xl border bg-muted/30 p-3 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Additional reserve
                        </span>
                        <strong>{money(total)}</strong>
                    </div>
                    <div className="mt-2 flex justify-between">
                        <span className="text-muted-foreground">
                            Available after
                        </span>
                        <strong
                            className={
                                after < 0 ? 'text-destructive' : 'text-success'
                            }
                        >
                            {money(after)}
                        </strong>
                    </div>
                </div>
                <ErrorSummary errors={form.errors} />
            </div>
        </Shell>
    );
}

export function RecordUsageDialog({
    campaign,
    beneficiaries,
    open,
    onOpenChange,
}: DialogProps & { beneficiaries: WorkspaceBeneficiary[] }) {
    const form = useForm({
        benefit: 'gp',
        beneficiary_id: '',
        quantity: '1',
        amount: '',
    });
    const monetary =
        form.data.benefit === 'medication' ||
        form.data.benefit === 'laboratory';
    const submit = () =>
        form.post(RecordCampaignUsageController.url(campaign.slug), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title="Record usage"
            description="Record a beneficiary using care. This converts reserved allocation into utilized money."
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={submit}>
                        Record
                    </Button>
                </>
            }
        >
            <div className="space-y-3">
                <Field label="Benefit" error={form.errors.benefit}>
                    <select
                        className={controlClass}
                        value={form.data.benefit}
                        onChange={(event) =>
                            form.setData('benefit', event.target.value)
                        }
                    >
                        <option value="gp">GP consultation</option>
                        <option value="specialist">
                            Specialist consultation
                        </option>
                        <option value="medication">Medication</option>
                        <option value="laboratory">Laboratory</option>
                    </select>
                </Field>
                <Field label="Beneficiary" error={form.errors.beneficiary_id}>
                    <select
                        className={controlClass}
                        value={form.data.beneficiary_id}
                        onChange={(event) =>
                            form.setData('beneficiary_id', event.target.value)
                        }
                    >
                        <option value="">Select beneficiary</option>
                        {beneficiaries.map((beneficiary) => (
                            <option key={beneficiary.id} value={beneficiary.id}>
                                {beneficiary.name}
                            </option>
                        ))}
                    </select>
                </Field>
                {monetary ? (
                    <Field label="Amount used (₦)" error={form.errors.amount}>
                        <Input
                            value={form.data.amount}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                        />
                    </Field>
                ) : (
                    <Field
                        label="Consultations used"
                        error={form.errors.quantity}
                    >
                        <Input
                            type="number"
                            min="1"
                            value={form.data.quantity}
                            onChange={(event) =>
                                form.setData('quantity', event.target.value)
                            }
                        />
                    </Field>
                )}
                <ErrorSummary errors={form.errors} />
            </div>
        </Shell>
    );
}

export function EnrollBeneficiaryDialog({
    campaign,
    open,
    onOpenChange,
}: DialogProps) {
    const defaultCommunity = campaign.location?.split(',')[0]?.trim() ?? '';
    const form = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        community: defaultCommunity,
    });
    const submit = () =>
        form.post(StoreCampaignBeneficiaryController.url(campaign.slug), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title="Enroll a beneficiary"
            description={`They will be funded by ${campaign.name}.`}
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={submit}>
                        Enroll
                    </Button>
                </>
            }
        >
            <div className="grid gap-3 sm:grid-cols-2">
                {(['first_name', 'last_name'] as const).map((field) => (
                    <Field
                        key={field}
                        label={
                            field === 'first_name' ? 'First name' : 'Last name'
                        }
                        error={form.errors[field]}
                    >
                        <Input
                            value={form.data[field]}
                            onChange={(event) =>
                                form.setData(field, event.target.value)
                            }
                        />
                    </Field>
                ))}
                <div className="sm:col-span-2">
                    <Field label="Email" error={form.errors.email}>
                        <Input
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                    </Field>
                </div>
                <Field label="Phone" error={form.errors.phone}>
                    <Input
                        value={form.data.phone}
                        onChange={(event) =>
                            form.setData('phone', event.target.value)
                        }
                    />
                </Field>
                <Field label="Community" error={form.errors.community}>
                    <Input
                        value={form.data.community}
                        onChange={(event) =>
                            form.setData('community', event.target.value)
                        }
                    />
                </Field>
                <div className="sm:col-span-2">
                    <ErrorSummary errors={form.errors} />
                </div>
            </div>
        </Shell>
    );
}

export function ImportBeneficiariesDialog({
    campaign,
    open,
    onOpenChange,
}: DialogProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const form = useForm<{ file: File | null; rows: string }>({
        file: null,
        rows: '',
    });
    const submit = () =>
        form.post(ImportCampaignBeneficiariesController.url(campaign.slug), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title="Upload beneficiaries"
            description={`Valid rows are enrolled into ${campaign.name}; invalid rows are skipped.`}
            wide
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        disabled={
                            form.processing ||
                            (!form.data.file && !form.data.rows)
                        }
                        onClick={submit}
                    >
                        <Upload className="size-4" />
                        Process rows
                    </Button>
                </>
            }
        >
            <div className="space-y-4">
                <button
                    type="button"
                    className="flex min-h-28 w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-muted/20 text-sm"
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload className="size-6 text-muted-foreground" />
                    <span className="font-medium">
                        {form.data.file?.name ?? 'Upload CSV or Excel file'}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        .csv, .xlsx
                    </span>
                </button>
                <input
                    ref={inputRef}
                    type="file"
                    accept=".csv,.xlsx"
                    className="hidden"
                    onChange={(event) =>
                        form.setData('file', event.target.files?.[0] ?? null)
                    }
                />
                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <span className="h-px flex-1 bg-border" />
                    or paste rows
                    <span className="h-px flex-1 bg-border" />
                </div>
                <p className="text-sm font-medium">
                    Columns: First, Last, Email, Phone, Community
                </p>
                <Textarea
                    rows={6}
                    value={form.data.rows}
                    placeholder={
                        'FirstName,LastName,Email,Phone,Community\nSani,Abubakar,sani@mail.com,+2348031110001,Sabon Gari'
                    }
                    onChange={(event) =>
                        form.setData('rows', event.target.value)
                    }
                />
                {(form.errors.file || form.errors.rows) && (
                    <p className="text-sm text-destructive">
                        {form.errors.file ?? form.errors.rows}
                    </p>
                )}
                <ErrorSummary errors={form.errors} />
            </div>
        </Shell>
    );
}

export function ImportResultDialog({
    campaign,
    result,
    open,
    onOpenChange,
}: DialogProps & { result: EmployeeImportResult }) {
    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title="Upload beneficiaries"
            description={`Everyone in this file was processed for ${campaign.name}.`}
            wide
            footer={<Button onClick={() => onOpenChange(false)}>Done</Button>}
        >
            <div className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-3">
                    <ResultMetric
                        label="Rows processed"
                        value={result.processed}
                    />
                    <ResultMetric
                        label="Enrolled"
                        value={result.imported}
                        success
                    />
                    <ResultMetric
                        label="Skipped"
                        value={result.skipped}
                        destructive
                    />
                </div>
                {result.errors.length > 0 && (
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center justify-between border-b px-4 py-3 text-sm font-medium">
                            <span className="inline-flex items-center gap-2">
                                <TriangleAlert className="size-4 text-destructive" />
                                Error log
                            </span>
                            {result.id && (
                                <a
                                    className="inline-flex h-9 items-center gap-2 rounded-control border px-3"
                                    href={DownloadCampaignImportErrorsController.url(
                                        {
                                            campaign: campaign.slug,
                                            import: result.id,
                                        },
                                    )}
                                >
                                    <Download className="size-4" />
                                    Download CSV
                                </a>
                            )}
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/30 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-2">ROW</th>
                                        <th className="px-4 py-2">
                                            IDENTIFIER
                                        </th>
                                        <th className="px-4 py-2">CODE</th>
                                        <th className="px-4 py-2">MESSAGE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {result.errors.map((error) => (
                                        <tr
                                            key={`${error.row}-${error.code}`}
                                            className="border-t"
                                        >
                                            <td className="px-4 py-3">
                                                {error.row}
                                            </td>
                                            <td className="px-4 py-3">
                                                {error.identifier ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="rounded-full bg-destructive/10 px-2 py-1 text-xs text-destructive">
                                                    {error.code}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {error.message}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </Shell>
    );
}

function ResultMetric({
    label,
    value,
    success,
    destructive,
}: {
    label: string;
    value: number;
    success?: boolean;
    destructive?: boolean;
}) {
    return (
        <div
            className={`rounded-xl border p-4 text-center ${success ? 'border-success/30 bg-success/5' : destructive ? 'border-destructive/30 bg-destructive/5' : ''}`}
        >
            <strong
                className={`block text-2xl ${success ? 'text-success' : destructive ? 'text-destructive' : ''}`}
            >
                {value}
            </strong>
            <span className="text-xs text-muted-foreground">{label}</span>
        </div>
    );
}

export function AddBoothDialog({
    campaign,
    detail,
    open,
    onOpenChange,
}: DialogProps & { detail: CampaignDetail }) {
    const form = useForm({
        count: '1',
        preferred_deployment_date: '',
        site: '',
        community: campaign.location?.split(',')[0]?.trim() ?? '',
        expected_beneficiaries: '',
        contact_name: '',
        contact_phone: '',
    });
    const setup =
        Number(form.data.count || 0) *
        Number(detail.configuration.boothSetupUnitFee);
    const submit = () =>
        form.post(AddCampaignBoothsController.url(campaign.slug), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title="Add a Health Bubba Booth"
            description="Each booth is billed on its own activation date and monthly cycle."
            wide
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={submit}>
                        Pay {money(setup)} & request
                    </Button>
                </>
            }
        >
            <div className="grid gap-3 sm:grid-cols-2">
                <Field label="Number of booths" error={form.errors.count}>
                    <Input
                        type="number"
                        min="1"
                        value={form.data.count}
                        onChange={(event) =>
                            form.setData('count', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Preferred deployment date"
                    error={form.errors.preferred_deployment_date}
                >
                    <Input
                        type="date"
                        value={form.data.preferred_deployment_date}
                        onChange={(event) =>
                            form.setData(
                                'preferred_deployment_date',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <div className="sm:col-span-2">
                    <Field label="Booth site" error={form.errors.site}>
                        <Input
                            value={form.data.site}
                            onChange={(event) =>
                                form.setData('site', event.target.value)
                            }
                        />
                    </Field>
                </div>
                <Field label="Community" error={form.errors.community}>
                    <Input
                        value={form.data.community}
                        onChange={(event) =>
                            form.setData('community', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Expected beneficiaries"
                    error={form.errors.expected_beneficiaries}
                >
                    <Input
                        type="number"
                        min="1"
                        value={form.data.expected_beneficiaries}
                        onChange={(event) =>
                            form.setData(
                                'expected_beneficiaries',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field label="On-site contact" error={form.errors.contact_name}>
                    <Input
                        value={form.data.contact_name}
                        onChange={(event) =>
                            form.setData('contact_name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Contact phone" error={form.errors.contact_phone}>
                    <Input
                        value={form.data.contact_phone}
                        onChange={(event) =>
                            form.setData('contact_phone', event.target.value)
                        }
                    />
                </Field>
                <div className="rounded-xl border bg-muted/30 p-3 text-sm sm:col-span-2">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Setup & deployment ({form.data.count || 0} ×{' '}
                            {money(detail.configuration.boothSetupUnitFee)})
                        </span>
                        <strong>{money(setup)}</strong>
                    </div>
                    <div className="mt-2 flex justify-between">
                        <span className="text-muted-foreground">
                            Management & service
                        </span>
                        <span>
                            {money(detail.configuration.boothMonthlyUnitFee)} /
                            month
                        </span>
                    </div>
                    <p className="mt-3 text-xs text-muted-foreground">
                        Only setup is charged now. Monthly service begins when
                        Health Bubba confirms deployment.
                    </p>
                </div>
                <div className="sm:col-span-2">
                    <ErrorSummary errors={form.errors} />
                </div>
            </div>
        </Shell>
    );
}

export function EndCampaignDialog({
    campaign,
    open,
    onOpenChange,
}: DialogProps) {
    const form = useForm({});
    const remaining = campaign.financial?.reserved ?? '0';
    const submit = () =>
        form.post(EndCampaignController.url(campaign.slug), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });

    return (
        <Shell
            open={open}
            onOpenChange={onOpenChange}
            title={`End ${campaign.name}?`}
            description="The campaign stops funding care and its beneficiaries lose coverage under it."
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={submit}>
                        End campaign
                    </Button>
                </>
            }
        >
            <div className="space-y-3">
                <div className="rounded-xl border bg-muted/30 p-3 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">
                            Unused allocation
                        </span>
                        <strong>{money(remaining)}</strong>
                    </div>
                    <div className="mt-3 flex justify-between border-t pt-3">
                        <span className="text-muted-foreground">
                            Returned to available balance
                        </span>
                        <strong className="text-success">
                            {money(remaining)}
                        </strong>
                    </div>
                </div>
                <div className="text-warning-foreground flex gap-2 rounded-xl border border-warning/30 bg-warning/10 p-3 text-sm">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                    <span>
                        Booths run until the end of the already-paid service
                        period, then stop. No future monthly fees are charged.
                    </span>
                </div>
            </div>
        </Shell>
    );
}

export function FeeNotice({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex gap-2 rounded-xl border bg-muted/30 p-3 text-sm text-muted-foreground">
            <Info className="mt-0.5 size-4 shrink-0" />
            {children}
        </div>
    );
}

function money(value: string | number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value));
}
