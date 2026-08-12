import { Form, Head } from '@inertiajs/react';
import {
    CheckCircle2Icon,
    Clock3Icon,
    ShieldCheckIcon,
    XCircleIcon,
} from 'lucide-react';

import { BrandMark } from '@/components/brand-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { MedicalAccessRequest, MedicalAccessRequestStatus } from '@/types';

interface MedicalAccessReviewPageProps {
    accessRequest: MedicalAccessRequest;
    decisionUrl: string | null;
}

const statusCopy: Record<MedicalAccessRequestStatus, string> = {
    pending:
        'Review what your sponsor is requesting, then choose whether to allow or deny access.',
    approved:
        'You allowed this request. The consent grant remains active for 30 days from approval.',
    denied: 'You denied this request. No medical access was granted.',
    expired:
        'This request or its approved access period has expired. No further action is available.',
};

export default function MedicalAccessReviewPage({
    accessRequest,
    decisionUrl,
}: MedicalAccessReviewPageProps) {
    const isPending =
        accessRequest.status === 'pending' && decisionUrl !== null;

    return (
        <main className="min-h-screen bg-muted/40 px-4 py-8 sm:flex sm:items-center sm:justify-center sm:py-12">
            <Head title="Review medical access request" />
            <div className="mx-auto w-full max-w-xl">
                <div className="mb-6 flex justify-center">
                    <BrandMark showName />
                </div>
                <Card className="overflow-hidden shadow-card">
                    <CardHeader className="border-b px-6 py-6 text-center sm:px-8">
                        <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-success-muted text-success">
                            <StatusIcon status={accessRequest.status} />
                        </div>
                        <Badge
                            variant={
                                accessRequest.status === 'approved'
                                    ? 'success'
                                    : accessRequest.status === 'pending'
                                      ? 'warning'
                                      : accessRequest.status === 'denied'
                                        ? 'destructive'
                                        : 'secondary'
                            }
                            className="mx-auto capitalize"
                        >
                            {accessRequest.status}
                        </Badge>
                        <CardTitle className="pt-2 text-xl">
                            Medical access request
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5 px-6 py-6 sm:px-8">
                        <p className="text-center text-sm leading-6 text-muted-foreground">
                            {statusCopy[accessRequest.status]}
                        </p>
                        <dl className="grid gap-4 rounded-2xl border bg-card p-4 text-sm">
                            <Detail
                                label="Beneficiary"
                                value={accessRequest.beneficiary.name}
                            />
                            <Detail
                                label="Sponsor"
                                value={accessRequest.workspace.name}
                            />
                            <Detail
                                label="Data requested"
                                value={accessRequest.dataType.label}
                            />
                            {accessRequest.reason && (
                                <Detail
                                    label="Reason"
                                    value={accessRequest.reason}
                                />
                            )}
                            <Detail
                                label="Review deadline"
                                value={formatDate(
                                    accessRequest.reviewExpiresAt,
                                )}
                            />
                            {accessRequest.accessExpiresAt && (
                                <Detail
                                    label="Access expires"
                                    value={formatDate(
                                        accessRequest.accessExpiresAt,
                                    )}
                                />
                            )}
                        </dl>
                        {isPending && (
                            <p className="rounded-xl bg-muted px-4 py-3 text-xs leading-5 text-muted-foreground">
                                Allowing records your consent for this data type
                                for 30 days. Medical-record viewing is not part
                                of this request page.
                            </p>
                        )}
                    </CardContent>
                    {isPending && (
                        <CardFooter className="border-t px-6 py-5 sm:px-8">
                            <Form
                                action={decisionUrl}
                                method="post"
                                className="grid w-full gap-3 sm:grid-cols-2"
                            >
                                {({ processing }) => (
                                    <>
                                        <Button
                                            name="decision"
                                            value="deny"
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            Deny
                                        </Button>
                                        <Button
                                            name="decision"
                                            value="allow"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Updating…'
                                                : 'Allow access'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardFooter>
                    )}
                </Card>
                <p className="pt-5 text-center text-xs leading-5 text-muted-foreground">
                    This secure consent page does not require you to sign in.
                </p>
            </div>
        </main>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 sm:grid-cols-[132px_1fr] sm:gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium break-words">{value}</dd>
        </div>
    );
}

function StatusIcon({ status }: { status: MedicalAccessRequestStatus }) {
    if (status === 'pending') {
        return <Clock3Icon className="size-6" />;
    }

    if (status === 'approved') {
        return <CheckCircle2Icon className="size-6" />;
    }

    if (status === 'denied') {
        return <XCircleIcon className="size-6" />;
    }

    return <ShieldCheckIcon className="size-6" />;
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
