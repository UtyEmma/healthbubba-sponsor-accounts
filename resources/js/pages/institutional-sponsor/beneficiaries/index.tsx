import { Head, router, useForm, usePage } from '@inertiajs/react';
import { DownloadIcon, EllipsisIcon, PlusIcon, UploadIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import ImportInstitutionalBeneficiariesController from '@/actions/App/Http/Controllers/Institutional/ImportInstitutionalBeneficiariesController';
import StoreInstitutionalBeneficiaryController from '@/actions/App/Http/Controllers/Institutional/StoreInstitutionalBeneficiaryController';
import DownloadCampaignImportErrorsController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/DownloadCampaignImportErrorsController';
import UpdateCampaignBeneficiaryAccessController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/UpdateCampaignBeneficiaryAccessController';
import { BeneficiaryImportTemplateLink } from '@/components/beneficiary-import-template-link';
import { PageHeader } from '@/components/page-header';
import { RosterPagination } from '@/components/roster-pagination';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { DashboardLayout } from '@/layouts/dashboard';
import institutional from '@/routes/institutional';
import type {
    InstitutionalBeneficiary,
    InstitutionalBeneficiaryPageProps,
    InstitutionalBeneficiaryStatus,
    InstitutionalCampaignOption,
    InstitutionalImportResult,
} from '@/types';

const statuses: InstitutionalBeneficiaryStatus[] = [
    'added',
    'invited',
    'registered',
    'active',
    'inactive',
    'suspended',
];

export default function InstitutionalBeneficiariesPage({
    roster,
    filters,
    importResult,
}: InstitutionalBeneficiaryPageProps) {
    const { flash, workspacePermissions } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [addOpen, setAddOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [resultOpen, setResultOpen] = useState(Boolean(importResult));
    const firstSearch = useRef(true);

    const visit = useCallback(
        (changes: Record<string, string>) => {
            router.get(
                institutional.beneficiaries.index().url,
                {
                    search,
                    campaign: filters.campaign ?? '',
                    status: filters.status ?? '',
                    ...changes,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        },
        [filters.campaign, filters.status, search],
    );

    useEffect(() => {
        if (firstSearch.current) {
            firstSearch.current = false;

            return;
        }

        const timeout = window.setTimeout(() => visit({ search }), 350);

        return () => window.clearTimeout(timeout);
    }, [search, visit]);

    return (
        <>
            <Head title="Beneficiaries" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl space-y-4">
                    <PageHeader
                        title="Beneficiaries"
                        description="Everyone covered by your program. Each person is funded by exactly one campaign."
                        action={
                            workspacePermissions.canManage ? (
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => setImportOpen(true)}
                                    >
                                        Bulk upload
                                    </Button>
                                    <Button onClick={() => setAddOpen(true)}>
                                        <PlusIcon className="size-4" /> Add
                                        beneficiary
                                    </Button>
                                </div>
                            ) : undefined
                        }
                    />

                    {flash.success && (
                        <p className="rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                            {flash.success}
                        </p>
                    )}

                    <section
                        className="grid grid-cols-2 gap-3 lg:grid-cols-6"
                        aria-label="Beneficiary status summary"
                    >
                        {statuses.map((status) => (
                            <Card key={status}>
                                <CardContent className="p-4">
                                    <p className="text-lg font-semibold">
                                        {roster.counts[status]}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground capitalize">
                                        {status}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </section>

                    <Card className="overflow-hidden">
                        <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
                            <h2 className="text-base font-semibold">
                                {roster.beneficiaries.meta.total} beneficiaries
                            </h2>
                            <div className="grid gap-2 sm:grid-cols-3 lg:w-[560px]">
                                <Input
                                    value={search}
                                    placeholder="Search name, email, community..."
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                />
                                <Select
                                    value={filters.campaign ?? ''}
                                    onChange={(event) =>
                                        visit({ campaign: event.target.value })
                                    }
                                >
                                    <option value="">All campaigns</option>
                                    {roster.campaigns.map((campaign) => (
                                        <option
                                            key={campaign.slug}
                                            value={campaign.slug}
                                        >
                                            {campaign.name}
                                        </option>
                                    ))}
                                </Select>
                                <Select
                                    value={filters.status ?? ''}
                                    onChange={(event) =>
                                        visit({ status: event.target.value })
                                    }
                                >
                                    <option value="">All statuses</option>
                                    {statuses.map((status) => (
                                        <option
                                            key={status}
                                            value={status}
                                            className="capitalize"
                                        >
                                            {capitalize(status)}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <Table className="min-w-[900px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Beneficiary</TableHead>
                                        <TableHead>Campaign</TableHead>
                                        <TableHead>Contact</TableHead>
                                        <TableHead>Community</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-12" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {roster.beneficiaries.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="h-28 text-center text-muted-foreground"
                                            >
                                                No beneficiaries match these
                                                filters.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        roster.beneficiaries.data.map(
                                            (beneficiary) => (
                                                <TableRow
                                                    key={beneficiary.publicId}
                                                >
                                                    <TableCell>
                                                        <div className="flex items-center gap-3">
                                                            <span className="flex size-8 items-center justify-center rounded-full bg-success-muted text-xs font-medium text-success">
                                                                {initials(
                                                                    beneficiary,
                                                                )}
                                                            </span>
                                                            <span className="font-medium">
                                                                {
                                                                    beneficiary.name
                                                                }
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {beneficiary.campaign
                                                            ?.name ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <p>
                                                            {beneficiary.email}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {beneficiary.phone}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                                            {beneficiary.community ??
                                                                '—'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusLabel
                                                            status={
                                                                beneficiary.status
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        {workspacePermissions.canManage &&
                                                            beneficiary.campaign && (
                                                                <BeneficiaryActions
                                                                    beneficiary={
                                                                        beneficiary
                                                                    }
                                                                />
                                                            )}
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        <RosterPagination pagination={roster.beneficiaries} />
                    </Card>
                </div>
            </DashboardLayout>

            <AddBeneficiaryDialog
                open={addOpen}
                onOpenChange={setAddOpen}
                campaigns={roster.campaigns.filter(
                    (campaign) => !campaign.ended,
                )}
            />
            <ImportBeneficiariesDialog
                open={importOpen}
                onOpenChange={setImportOpen}
                onCompleted={() => setResultOpen(true)}
                campaigns={roster.campaigns.filter(
                    (campaign) => !campaign.ended,
                )}
            />
            {importResult && (
                <ImportResultDialog
                    result={importResult}
                    open={resultOpen}
                    onOpenChange={setResultOpen}
                />
            )}
        </>
    );
}

function AddBeneficiaryDialog({
    open,
    onOpenChange,
    campaigns,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    campaigns: InstitutionalCampaignOption[];
}) {
    const form = useForm({
        campaign: campaigns[0]?.slug ?? '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        community: campaigns[0]?.location?.split(',')[0]?.trim() ?? '',
    });
    const selected = campaigns.find(
        (campaign) => campaign.slug === form.data.campaign,
    );
    const submit = () =>
        form.post(StoreInstitutionalBeneficiaryController.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle className="text-base">
                        Enroll a beneficiary
                    </DialogTitle>
                    <DialogDescription>
                        Choose the campaign that will fund this person.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-3 px-6 py-5 sm:grid-cols-2">
                    <Field label="Campaign" error={form.errors.campaign} wide>
                        <Select
                            value={form.data.campaign}
                            onChange={(event) => {
                                const campaign = campaigns.find(
                                    (item) => item.slug === event.target.value,
                                );
                                form.setData((data) => ({
                                    ...data,
                                    campaign: event.target.value,
                                    community:
                                        campaign?.location
                                            ?.split(',')[0]
                                            ?.trim() ?? '',
                                }));
                            }}
                        >
                            {campaigns.map((campaign) => (
                                <option
                                    key={campaign.slug}
                                    value={campaign.slug}
                                >
                                    {campaign.name}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="First name" error={form.errors.first_name}>
                        <Input
                            value={form.data.first_name}
                            onChange={(event) =>
                                form.setData('first_name', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Last name" error={form.errors.last_name}>
                        <Input
                            value={form.data.last_name}
                            onChange={(event) =>
                                form.setData('last_name', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Email" error={form.errors.email} wide>
                        <Input
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                    </Field>
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
                            placeholder={selected?.location ?? ''}
                            onChange={(event) =>
                                form.setData('community', event.target.value)
                            }
                        />
                    </Field>
                </div>
                <DialogFooter className="justify-between sm:justify-between">
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        disabled={form.processing || campaigns.length === 0}
                        onClick={submit}
                    >
                        {form.processing ? 'Enrolling...' : 'Enroll'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ImportBeneficiariesDialog({
    open,
    onOpenChange,
    onCompleted,
    campaigns,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCompleted: () => void;
    campaigns: InstitutionalCampaignOption[];
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const form = useForm<{ campaign: string; file: File | null; rows: string }>(
        { campaign: campaigns[0]?.slug ?? '', file: null, rows: '' },
    );
    const submit = () =>
        form.post(ImportInstitutionalBeneficiariesController.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('file', 'rows');
                onOpenChange(false);
                onCompleted();
            },
        });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[672px]">
                <DialogHeader>
                    <DialogTitle className="text-base">
                        Upload beneficiaries
                    </DialogTitle>
                    <DialogDescription>
                        Choose one campaign. Valid rows are committed and
                        invalid rows are skipped.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4 px-6 pb-5">
                    <Field label="Campaign" error={form.errors.campaign}>
                        <Select
                            value={form.data.campaign}
                            onChange={(event) =>
                                form.setData('campaign', event.target.value)
                            }
                        >
                            {campaigns.map((campaign) => (
                                <option
                                    key={campaign.slug}
                                    value={campaign.slug}
                                >
                                    {campaign.name}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <button
                        type="button"
                        className="flex min-h-28 w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-muted/20 text-sm"
                        onClick={() => inputRef.current?.click()}
                    >
                        <UploadIcon className="size-6 text-muted-foreground" />
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
                            form.setData(
                                'file',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm font-medium">
                            Or paste: First, Last, Email, Phone, Community
                        </p>
                        <BeneficiaryImportTemplateLink />
                    </div>
                    <Textarea
                        rows={6}
                        value={form.data.rows}
                        onChange={(event) =>
                            form.setData('rows', event.target.value)
                        }
                        placeholder="FirstName,LastName,Email,Phone,Community"
                    />
                    {(form.errors.file || form.errors.rows) && (
                        <p className="text-sm text-destructive">
                            {form.errors.file ?? form.errors.rows}
                        </p>
                    )}
                </div>
                <DialogFooter className="justify-between sm:justify-between">
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        disabled={
                            form.processing ||
                            (!form.data.file && !form.data.rows) ||
                            !form.data.campaign
                        }
                        onClick={submit}
                    >
                        <UploadIcon className="size-4" />
                        {form.processing ? 'Processing...' : 'Process rows'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ImportResultDialog({
    result,
    open,
    onOpenChange,
}: {
    result: InstitutionalImportResult;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const errorDownload = result.id
        ? DownloadCampaignImportErrorsController.url({
              campaign: result.campaignSlug,
              import: result.id,
          })
        : null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[672px]">
                <DialogHeader>
                    <DialogTitle className="text-base">
                        Upload beneficiaries
                    </DialogTitle>
                    <DialogDescription>
                        Valid rows were enrolled and invalid rows were skipped.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-3 px-6 pb-5">
                    <div className="grid grid-cols-3 gap-3">
                        {[
                            ['Rows processed', result.processed],
                            ['Enrolled', result.imported],
                            ['Skipped', result.skipped],
                        ].map(([label, value]) => (
                            <div
                                key={label}
                                className="rounded-xl border p-4 text-center"
                            >
                                <strong className="block text-xl">
                                    {value}
                                </strong>
                                <span className="text-xs text-muted-foreground">
                                    {label}
                                </span>
                            </div>
                        ))}
                    </div>
                    {result.errors.length > 0 && (
                        <div className="max-h-60 overflow-auto rounded-xl border">
                            {result.errors.map((error) => (
                                <div
                                    key={`${error.row}-${error.code}`}
                                    className="grid grid-cols-[44px_1fr] gap-2 border-b p-3 text-sm last:border-b-0"
                                >
                                    <span>{error.row}</span>
                                    <div>
                                        <span className="text-xs font-medium text-destructive">
                                            {error.code}
                                        </span>
                                        <p className="text-muted-foreground">
                                            {error.message}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
                <DialogFooter className="justify-between sm:justify-between">
                    {errorDownload ? (
                        <a
                            href={errorDownload}
                            className={buttonVariants({ variant: 'outline' })}
                        >
                            <DownloadIcon className="size-4" />
                            Download errors
                        </a>
                    ) : (
                        <span />
                    )}
                    <Button onClick={() => onOpenChange(false)}>Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function BeneficiaryActions({
    beneficiary,
}: {
    beneficiary: InstitutionalBeneficiary;
}) {
    const actions =
        beneficiary.accessStatus === 'active'
            ? (['suspend', 'revoke'] as const)
            : beneficiary.accessStatus === 'suspended'
              ? (['restore', 'revoke'] as const)
              : [];
    const run = (action: 'suspend' | 'restore' | 'revoke') => {
        if (
            !beneficiary.campaign ||
            !window.confirm(
                `${capitalize(action)} access for ${beneficiary.name}?`,
            )
        ) {
            return;
        }

        router.patch(
            UpdateCampaignBeneficiaryAccessController.url({
                campaign: beneficiary.campaign.slug,
                workspaceBeneficiary: beneficiary.publicId,
            }),
            { action },
            { preserveScroll: true },
        );
    };

    return actions.length === 0 ? null : (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={`Actions for ${beneficiary.name}`}
                    />
                }
            >
                <EllipsisIcon className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {actions.map((action) => (
                    <DropdownMenuItem
                        key={action}
                        variant={
                            action === 'revoke' ? 'destructive' : 'default'
                        }
                        onClick={() => run(action)}
                    >
                        {action === 'revoke'
                            ? 'Remove beneficiary'
                            : `${capitalize(action)} access`}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Field({
    label,
    error,
    wide,
    children,
}: {
    label: string;
    error?: string;
    wide?: boolean;
    children: React.ReactNode;
}) {
    return (
        <label
            className={`grid gap-1.5 text-sm font-medium ${wide ? 'sm:col-span-2' : ''}`}
        >
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
function StatusLabel({ status }: { status: InstitutionalBeneficiaryStatus }) {
    const color =
        status === 'active' || status === 'registered'
            ? 'text-success'
            : status === 'suspended'
              ? 'text-destructive'
              : status === 'invited'
                ? 'text-warning'
                : 'text-muted-foreground';

    return (
        <span className={`text-sm font-medium ${color}`}>
            {capitalize(status)}
        </span>
    );
}
function initials(beneficiary: InstitutionalBeneficiary) {
    return `${beneficiary.firstName[0] ?? ''}${beneficiary.lastName[0] ?? ''}`.toUpperCase();
}
function capitalize(value: string) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
