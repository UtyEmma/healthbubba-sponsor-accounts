import { Head } from '@inertiajs/react';
import { CopyIcon, PlusIcon, TicketIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';

import { institutionalNavigation } from '../partials/institutional-navigation';

type EnrollmentCode = {
    code: string;
    program: string;
    enrolled: number;
    limit: number;
    community: string;
    expiry: string;
    status: 'Active' | 'Full' | 'Expired';
};

const initialCodes: EnrollmentCode[] = [
    {
        code: 'HOPE-SABON-2026',
        program: 'Community Health Program 2026',
        enrolled: 134,
        limit: 200,
        community: 'Sabon Gari',
        expiry: '19 October 2026',
        status: 'Active',
    },
    {
        code: 'HOPE-OGUI-2026',
        program: 'Community Health Program 2026',
        enrolled: 150,
        limit: 150,
        community: 'Ogui',
        expiry: '31 July 2026',
        status: 'Full',
    },
    {
        code: 'HOPE-TUDUN-2025',
        program: 'Community Health Program 2025',
        enrolled: 61,
        limit: 100,
        community: 'Tudun Wada',
        expiry: '16 June 2026',
        status: 'Expired',
    },
];

export default function EnrollmentCodesPage() {
    const [codes, setCodes] = useState(initialCodes);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [announcement, setAnnouncement] = useState('');

    function createCode(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const code = String(data.get('code'));
        const limit = Number(data.get('limit'));
        const community = String(data.get('community'));
        const expiry = String(data.get('expiry'));

        setCodes((items) => [
            ...items,
            {
                code,
                program: 'Community Health Program 2026',
                enrolled: 0,
                limit,
                community,
                expiry,
                status: 'Active',
            },
        ]);
        setAnnouncement(`${code} created.`);
        setDialogOpen(false);
    }

    async function copyCode(code: string) {
        await navigator.clipboard?.writeText(code);
        setAnnouncement(`${code} copied.`);
    }

    return (
        <>
            <Head title="Enrollment Codes" />
            <BusinessPortalShell
                navigation={institutionalNavigation}
                navigationLabel="Institutional sponsor navigation"
            >
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Enrollment Codes"
                        description="Issue codes that let beneficiaries self-register and activate coverage."
                        action={
                            <Button
                                size="compact"
                                onClick={() => setDialogOpen(true)}
                            >
                                <PlusIcon className="size-4" />
                                Create Codes
                            </Button>
                        }
                    />
                    <section
                        className="grid gap-4 pt-6 lg:grid-cols-2"
                        aria-label="Enrollment codes"
                    >
                        {codes.map((item) => (
                            <Card key={item.code}>
                                <CardContent className="p-6">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex gap-2">
                                            <TicketIcon className="mt-0.5 size-4 text-muted-foreground" />
                                            <div>
                                                <h2 className="font-semibold">
                                                    {item.code}
                                                </h2>
                                                <p className="pt-1 text-sm text-muted-foreground">
                                                    {item.program}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <CodeStatus status={item.status} />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Copy ${item.code}`}
                                                onClick={() =>
                                                    void copyCode(item.code)
                                                }
                                            >
                                                <CopyIcon className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="pt-6">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">
                                                Enrolled
                                            </span>
                                            <span className="font-medium">
                                                {item.enrolled} / {item.limit}
                                            </span>
                                        </div>
                                        <Progress
                                            className="mt-1"
                                            value={
                                                (item.enrolled / item.limit) *
                                                100
                                            }
                                        />
                                        <span className="mt-3 inline-flex rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground">
                                            {item.community}
                                        </span>
                                        <p className="pt-3 text-xs text-muted-foreground">
                                            Expires {item.expiry}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                    <CreateCodeDialog
                        open={dialogOpen}
                        onOpenChange={setDialogOpen}
                        onSubmit={createCode}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
        </>
    );
}

function CodeStatus({ status }: { status: EnrollmentCode['status'] }) {
    if (status === 'Active') return <Badge variant="success">Active</Badge>;
    if (status === 'Expired')
        return <Badge variant="destructive">Expired</Badge>;
    return (
        <Badge className="border-blue-200 bg-blue-100 text-blue-700">
            Full
        </Badge>
    );
}

function CreateCodeDialog({
    open,
    onOpenChange,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="border-b px-6 py-5">
                    <DialogTitle className="text-base">
                        Create enrollment code
                    </DialogTitle>
                    <DialogDescription>
                        Beneficiaries register, enter this code, and coverage
                        activates.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={onSubmit}>
                    <div className="grid gap-4 px-6 py-4">
                        <Field label="Code">
                            <Input
                                name="code"
                                placeholder="Enter code"
                                required
                            />
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Beneficiary limit">
                                <Input
                                    name="limit"
                                    type="number"
                                    min="1"
                                    placeholder="Enter number"
                                    required
                                />
                            </Field>
                            <Field label="Expiry Date">
                                <Input name="expiry" type="date" required />
                            </Field>
                        </div>
                        <Field label="Eligible Community">
                            <select
                                name="community"
                                required
                                defaultValue=""
                                className="h-10 rounded-control border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled>
                                    Select
                                </option>
                                <option>Sabon Gari</option>
                                <option>Ogui</option>
                                <option>Tudun Wada</option>
                            </select>
                        </Field>
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
                            Send Code
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="grid gap-2 text-[13px] font-medium">
            {label}
            {children}
        </label>
    );
}
