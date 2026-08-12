import { Form, Head } from '@inertiajs/react';
import { CheckCircle2Icon, Clock3Icon, XCircleIcon } from 'lucide-react';

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
import type { WorkspaceBeneficiaryStatus } from '@/types';

interface InvitationPageProps {
    invitation: {
        name: string;
        workspaceName: string;
        status: WorkspaceBeneficiaryStatus;
        expiresAt: string;
    };
    decisionUrl: string | null;
}

const statusCopy: Record<WorkspaceBeneficiaryStatus, string> = {
    pending:
        'Review the invitation below and choose whether to accept or decline it.',
    active: 'You accepted this invitation. Your sponsorship slot is now active.',
    suspended:
        'Your sponsor has temporarily suspended access. Contact your sponsor for more information.',
    revoked: 'Your sponsor has revoked this healthcare sponsorship access.',
    declined: 'You declined this invitation. No further action is required.',
    cancelled: 'This invitation was cancelled by the sponsor.',
    expired: 'This invitation expired and can no longer be accepted.',
};

export default function WorkspaceBeneficiaryInvitationPage({
    invitation,
    decisionUrl,
}: InvitationPageProps) {
    const isPending = invitation.status === 'pending' && decisionUrl !== null;

    return (
        <main className="min-h-screen bg-muted/40 px-4 py-8 sm:flex sm:items-center sm:justify-center sm:py-12">
            <Head title="Healthcare invitation" />
            <div className="mx-auto w-full max-w-lg">
                <div className="mb-6 flex justify-center">
                    <BrandMark showName />
                </div>
                <Card className="overflow-hidden shadow-card">
                    <CardHeader className="border-b px-6 py-6 text-center sm:px-8">
                        <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-success-muted text-success">
                            {invitation.status === 'pending' ? (
                                <Clock3Icon className="size-6" />
                            ) : invitation.status === 'active' ? (
                                <CheckCircle2Icon className="size-6" />
                            ) : (
                                <XCircleIcon className="size-6" />
                            )}
                        </div>
                        <Badge
                            variant={
                                invitation.status === 'active'
                                    ? 'success'
                                    : invitation.status === 'suspended'
                                      ? 'warning'
                                      : invitation.status === 'revoked'
                                        ? 'destructive'
                                        : invitation.status === 'pending'
                                          ? 'warning'
                                          : 'secondary'
                            }
                            className="mx-auto capitalize"
                        >
                            {invitation.status}
                        </Badge>
                        <CardTitle className="pt-2 text-xl">
                            Healthcare sponsorship invitation
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5 px-6 py-6 sm:px-8">
                        <p className="text-center text-sm leading-6 text-muted-foreground">
                            {statusCopy[invitation.status]}
                        </p>
                        <dl className="grid gap-4 rounded-2xl border bg-card p-4 text-sm">
                            <div className="grid gap-1 sm:grid-cols-[120px_1fr] sm:gap-4">
                                <dt className="text-muted-foreground">
                                    Invitee
                                </dt>
                                <dd className="font-medium">
                                    {invitation.name}
                                </dd>
                            </div>
                            <div className="grid gap-1 sm:grid-cols-[120px_1fr] sm:gap-4">
                                <dt className="text-muted-foreground">
                                    Sponsor
                                </dt>
                                <dd className="font-medium">
                                    {invitation.workspaceName}
                                </dd>
                            </div>
                            <div className="grid gap-1 sm:grid-cols-[120px_1fr] sm:gap-4">
                                <dt className="text-muted-foreground">
                                    Expires
                                </dt>
                                <dd>{formatDate(invitation.expiresAt)}</dd>
                            </div>
                        </dl>
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
                                            value="decline"
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            Decline
                                        </Button>
                                        <Button
                                            name="decision"
                                            value="accept"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Updating…'
                                                : 'Accept invitation'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardFooter>
                    )}
                </Card>
                <p className="pt-5 text-center text-xs leading-5 text-muted-foreground">
                    This public page does not require a HealthBubba account.
                </p>
            </div>
        </main>
    );
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value));
}
