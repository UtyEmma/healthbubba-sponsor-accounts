import { Head, useForm, usePage } from '@inertiajs/react';
import { CopyIcon, PlusIcon } from 'lucide-react';
import { useState } from 'react';

import StoreEnrollmentCodeController from '@/actions/App/Http/Controllers/Institutional/StoreEnrollmentCodeController';
import { PageHeader } from '@/components/page-header';
import { RosterPagination } from '@/components/roster-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { DashboardLayout } from '@/layouts/dashboard';
import type {
    EnrollmentCodePageProps,
    InstitutionalCampaignOption,
} from '@/types';

export default function EnrollmentCodesPage({
    enrollmentCodes,
}: EnrollmentCodePageProps) {
    const { flash, workspacePermissions } = usePage().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [announcement, setAnnouncement] = useState('');
console.log(enrollmentCodes)
    return (
        <>
            <Head title="Enrollment Codes" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl space-y-4">
                    <PageHeader
                        title="Enrollment Codes"
                        description="Issue codes for a specific campaign. Codes are managed and shared by your team."
                        action={
                            workspacePermissions.canManage ? (
                                <Button onClick={() => setCreateOpen(true)}>
                                    <PlusIcon className="size-4" /> Create code
                                </Button>
                            ) : undefined
                        }
                    />
                    {flash.success && (
                        <p className="rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    {enrollmentCodes.codes.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center text-sm text-muted-foreground">
                                No enrollment codes have been created.
                            </CardContent>
                        </Card>
                    ) : (
                        <section
                            className="grid gap-4 lg:grid-cols-2"
                            aria-label="Campaign enrollment codes"
                        >
                            {enrollmentCodes.codes.map((code) => {
                                const percentage = Math.min(
                                    100,
                                    (code.enrolled /
                                        Math.max(1, code.enrollmentLimit)) *
                                        100,
                                );

                                return (
                                    <Card key={code.id}>
                                        <CardContent className="p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <h2 className="text-base font-semibold tracking-wide">
                                                        {code.code}
                                                    </h2>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        For{' '}
                                                        <span className="text-success">
                                                            {code.campaign.name}
                                                        </span>
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            code.status ===
                                                            'active'
                                                                ? 'success'
                                                                : 'secondary'
                                                        }
                                                        className={
                                                            code.status ===
                                                            'full'
                                                                ? 'bg-blue-50 text-blue-700'
                                                                : undefined
                                                        }
                                                    >
                                                        {code.statusLabel}
                                                    </Badge>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Copy ${code.code}`}
                                                        onClick={async () => {
                                                            await navigator.clipboard.writeText(
                                                                code.code,
                                                            );
                                                            setAnnouncement(
                                                                `${code.code} copied.`,
                                                            );
                                                        }}
                                                    >
                                                        <CopyIcon className="size-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className="mt-4 flex justify-between text-sm">
                                                <span className="font-medium">
                                                    Enrolled
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {code.enrolled} /{' '}
                                                    {code.enrollmentLimit}
                                                </span>
                                            </div>
                                            <Progress
                                                value={percentage}
                                                className="mt-2 h-1.5"
                                            />
                                            <div className="mt-4 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                                                <span className="rounded-full bg-muted px-2 py-1">
                                                    {code.campaign.location?.split(
                                                        ',',
                                                    )[0] ?? '—'}
                                                </span>
                                                <span>
                                                    Expires{' '}
                                                    {formatDate(code.expiresAt)}
                                                </span>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </section>
                    )}
                    {/* <RosterPagination pagination={enrollmentCodes.codes} /> */}
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </DashboardLayout>
            <CreateCodeDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                campaigns={enrollmentCodes.campaigns}
            />
        </>
    );
}

function CreateCodeDialog({
    open,
    onOpenChange,
    campaigns,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    campaigns: InstitutionalCampaignOption[];
}) {
    const first = campaigns[0];
    const form = useForm({
        campaign: first?.slug ?? '',
        enrollment_limit: String(first?.defaultLimit ?? 1),
        expires_at: first?.endDate ?? '',
    });
    const selectCampaign = (slug: string) => {
        const campaign = campaigns.find((item) => item.slug === slug);
        form.setData({
            campaign: slug,
            enrollment_limit: String(campaign?.defaultLimit ?? 1),
            expires_at: campaign?.endDate ?? '',
        });
    };
    const submit = () =>
        form.post(StoreEnrollmentCodeController.url(), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle className="text-base">
                        Create an enrollment code
                    </DialogTitle>
                    <DialogDescription>
                        The code is generated securely and belongs to the
                        selected campaign.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 px-6 pb-5">
                    <Field label="Campaign" error={form.errors.campaign}>
                        <select
                            className="h-10 rounded-control border border-input bg-background px-3 text-sm"
                            value={form.data.campaign}
                            onChange={(event) =>
                                selectCampaign(event.target.value)
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
                        </select>
                    </Field>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Field
                            label="Enrollment limit"
                            error={form.errors.enrollment_limit}
                        >
                            <Input
                                type="number"
                                min="1"
                                value={form.data.enrollment_limit}
                                onChange={(event) =>
                                    form.setData(
                                        'enrollment_limit',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Expiry date"
                            error={form.errors.expires_at}
                        >
                            <Input
                                type="date"
                                value={form.data.expires_at}
                                onChange={(event) =>
                                    form.setData(
                                        'expires_at',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
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
                        {form.processing ? 'Creating...' : 'Create code'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
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
function formatDate(value: string) {
    return new Intl.DateTimeFormat('en-NG', { dateStyle: 'long' }).format(
        new Date(value),
    );
}
