import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle2Icon, Clock3Icon, XCircleIcon } from 'lucide-react';

import { BrandMark } from '@/components/brand-mark';
import InputError from '@/components/input/input-error';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import type {
    WorkspaceMemberStatus,
    WorkspaceTeamInvitationReview,
} from '@/types';

interface InvitationPageProps {
    invitation: WorkspaceTeamInvitationReview;
    acceptUrl: string | null;
    declineUrl: string | null;
}

export default function WorkspaceTeamInvitationPage({
    invitation,
    acceptUrl,
    declineUrl,
}: InvitationPageProps) {
    const pending = invitation.status === 'invited';

    return (
        <main className="min-h-screen bg-muted/40 px-4 py-8 sm:flex sm:items-center sm:justify-center sm:py-12">
            <Head title="Team invitation" />
            <div className="mx-auto w-full max-w-lg">
                <div className="mb-6 flex justify-center">
                    <BrandMark showName />
                </div>
                <Card className="overflow-hidden shadow-card">
                    <CardHeader className="border-b px-6 py-6 text-center sm:px-8">
                        <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-success-muted text-success">
                            {pending ? (
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
                                    : pending
                                      ? 'warning'
                                      : 'secondary'
                            }
                            className="mx-auto capitalize"
                        >
                            {invitation.status}
                        </Badge>
                        <CardTitle className="pt-2 text-xl">
                            Workspace team invitation
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5 px-6 py-6 sm:px-8">
                        <p className="text-center text-sm leading-6 text-muted-foreground">
                            {pending
                                ? `${invitation.workspaceName} invited you to join its sponsor team.`
                                : terminalCopy(invitation.status)}
                        </p>
                        <dl className="grid gap-4 rounded-2xl border bg-card p-4 text-sm">
                            <Detail
                                label="Invitee"
                                value={`${invitation.name} (${invitation.email})`}
                            />
                            <Detail
                                label="Workspace"
                                value={invitation.workspaceName}
                            />
                            <Detail label="Role" value={invitation.roleLabel} />
                            {invitation.expiresAt && (
                                <Detail
                                    label="Expires"
                                    value={formatDate(invitation.expiresAt)}
                                />
                            )}
                        </dl>
                        {pending && invitation.wrongAccount && (
                            <p className="text-sm text-destructive">
                                You are signed in with a different email
                                address. Sign out and use {invitation.email} to
                                accept.
                            </p>
                        )}
                    </CardContent>

                    {pending && (
                        <CardFooter className="grid gap-3 border-t px-6 py-5 sm:px-8">
                            {invitation.existingAccount &&
                            !invitation.canAccept ? (
                                <Link
                                    href={login()}
                                    className={buttonVariants()}
                                >
                                    Sign in to accept
                                </Link>
                            ) : acceptUrl ? (
                                <Form
                                    action={acceptUrl}
                                    method="post"
                                    className="grid gap-3"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            {!invitation.existingAccount && (
                                                <div className="grid gap-3">
                                                    <label className="grid gap-2 text-sm font-medium">
                                                        Create password
                                                        <Input
                                                            name="password"
                                                            type="password"
                                                            autoComplete="new-password"
                                                            required
                                                        />
                                                        <InputError
                                                            error={
                                                                errors.password
                                                            }
                                                        />
                                                    </label>
                                                    <label className="grid gap-2 text-sm font-medium">
                                                        Confirm password
                                                        <Input
                                                            name="password_confirmation"
                                                            type="password"
                                                            autoComplete="new-password"
                                                            required
                                                        />
                                                    </label>
                                                </div>
                                            )}
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? 'Accepting...'
                                                    : 'Accept invitation'}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : null}
                            {declineUrl && (
                                <Form action={declineUrl} method="post">
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            Decline
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </CardFooter>
                    )}
                </Card>
            </div>
        </main>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 sm:grid-cols-[110px_1fr] sm:gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function terminalCopy(status: WorkspaceMemberStatus): string {
    return {
        active: 'You accepted this invitation and now have access to the workspace.',
        disabled: 'Your workspace access is currently disabled.',
        declined: 'You declined this invitation.',
        cancelled: 'This invitation was cancelled.',
        expired: 'This invitation has expired.',
        invited: 'Review this invitation.',
    }[status];
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value));
}
