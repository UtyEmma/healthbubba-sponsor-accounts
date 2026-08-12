import { Form } from '@inertiajs/react';
import { EllipsisIcon } from 'lucide-react';
import { useState } from 'react';

import CancelWorkspaceBeneficiaryInvitationController from '@/actions/App/Http/Controllers/WorkspaceBeneficiaries/CancelWorkspaceBeneficiaryInvitationController';
import ResendWorkspaceBeneficiaryInvitationController from '@/actions/App/Http/Controllers/WorkspaceBeneficiaries/ResendWorkspaceBeneficiaryInvitationController';
import UpdateWorkspaceBeneficiaryAccessController from '@/actions/App/Http/Controllers/WorkspaceBeneficiaries/UpdateWorkspaceBeneficiaryAccessController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
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
import type {
    WorkspaceBeneficiary,
    WorkspaceBeneficiaryAccessAction,
    WorkspaceBeneficiaryStatus,
} from '@/types';

type ConfirmationAction =
    'resend' | 'cancel' | WorkspaceBeneficiaryAccessAction;

const actionLabels: Record<ConfirmationAction, string> = {
    resend: 'Resend invite',
    cancel: 'Cancel invite',
    suspend: 'Suspend access',
    restore: 'Restore access',
    revoke: 'Revoke access',
};

export function WorkspaceInvitationActions({
    invitation,
}: {
    invitation: WorkspaceBeneficiary;
}) {
    const [confirmation, setConfirmation] = useState<ConfirmationAction | null>(
        null,
    );
    const actions = availableActions(invitation.status);

    if (actions.length === 0) {
        return (
            <span className="text-xs text-muted-foreground">No actions</span>
        );
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger
                    render={
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label={`Actions for ${invitation.name}`}
                        />
                    }
                >
                    <EllipsisIcon className="size-4" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-40">
                    {actions.map((action) => (
                        <DropdownMenuItem
                            key={action}
                            variant={
                                isDestructiveAction(action)
                                    ? 'destructive'
                                    : 'default'
                            }
                            onClick={() => setConfirmation(action)}
                        >
                            {actionLabels[action]}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            {confirmation !== null && (
                <InvitationActionConfirmation
                    invitation={invitation}
                    action={confirmation}
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setConfirmation(null);
                        }
                    }}
                />
            )}
        </>
    );
}

function InvitationActionConfirmation({
    invitation,
    action,
    open,
    onOpenChange,
}: {
    invitation: WorkspaceBeneficiary;
    action: ConfirmationAction;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const details = confirmationDetails(action, invitation);
    const form =
        action === 'cancel'
            ? CancelWorkspaceBeneficiaryInvitationController.form({
                  workspaceBeneficiary: invitation.publicId,
              })
            : action === 'resend'
              ? ResendWorkspaceBeneficiaryInvitationController.form({
                    workspaceBeneficiary: invitation.publicId,
                })
              : UpdateWorkspaceBeneficiaryAccessController.form({
                    workspaceBeneficiary: invitation.publicId,
                });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-2 border-b px-6 pt-6 pb-5">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        {details.title}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {details.description}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            {isAccessAction(action) && (
                                <input
                                    type="hidden"
                                    name="action"
                                    value={action}
                                />
                            )}
                            {(errors.invitation ||
                                errors.access ||
                                errors.capacity ||
                                errors.subscription) && (
                                <p
                                    className="mx-6 mt-5 rounded-xl border border-destructive-border bg-destructive-muted px-4 py-3 text-sm text-destructive"
                                    role="alert"
                                >
                                    {errors.invitation ??
                                        errors.access ??
                                        errors.capacity ??
                                        errors.subscription}
                                </p>
                            )}
                            <DialogFooter className="flex-row justify-end gap-3 px-6 py-5">
                                <DialogClose
                                    render={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="compact"
                                            disabled={processing}
                                        />
                                    }
                                >
                                    {details.cancelLabel}
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={
                                        isDestructiveAction(action)
                                            ? 'destructive'
                                            : 'primary'
                                    }
                                    size="compact"
                                    disabled={processing}
                                >
                                    {processing
                                        ? details.processingLabel
                                        : details.confirmLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function availableActions(
    status: WorkspaceBeneficiaryStatus,
): ConfirmationAction[] {
    switch (status) {
        case 'pending':
            return ['resend', 'cancel'];
        case 'active':
            return ['suspend', 'revoke'];
        case 'suspended':
            return ['restore', 'revoke'];
        case 'declined':
        case 'cancelled':
        case 'expired':
            return ['resend'];
        case 'revoked':
            return [];
    }
}

function isAccessAction(
    action: ConfirmationAction,
): action is WorkspaceBeneficiaryAccessAction {
    return action === 'suspend' || action === 'restore' || action === 'revoke';
}

function isDestructiveAction(action: ConfirmationAction): boolean {
    return action === 'cancel' || action === 'suspend' || action === 'revoke';
}

function confirmationDetails(
    action: ConfirmationAction,
    invitation: WorkspaceBeneficiary,
): {
    title: string;
    description: string;
    cancelLabel: string;
    confirmLabel: string;
    processingLabel: string;
} {
    switch (action) {
        case 'resend':
            return {
                title: 'Resend this invitation?',
                description: `A new invitation will be sent to ${invitation.email}. The previous signed link will stop working.`,
                cancelLabel: 'Go back',
                confirmLabel: 'Resend invitation',
                processingLabel: 'Resending…',
            };
        case 'cancel':
            return {
                title: 'Cancel this invitation?',
                description: `This will cancel ${invitation.name}'s invitation and release the reserved capacity.`,
                cancelLabel: 'Keep invitation',
                confirmLabel: 'Cancel invitation',
                processingLabel: 'Cancelling…',
            };
        case 'suspend':
            return {
                title: 'Suspend this access?',
                description: `${invitation.name} will temporarily lose sponsored healthcare access. Their seat remains reserved and can be restored later.`,
                cancelLabel: 'Keep access',
                confirmLabel: 'Suspend access',
                processingLabel: 'Suspending…',
            };
        case 'restore':
            return {
                title: 'Restore this access?',
                description: `${invitation.name} will regain sponsored healthcare access immediately.`,
                cancelLabel: 'Go back',
                confirmLabel: 'Restore access',
                processingLabel: 'Restoring…',
            };
        case 'revoke':
            return {
                title: 'Revoke this access?',
                description: `${invitation.name} will lose sponsored healthcare access and their reserved seat will be released. You can invite them again later.`,
                cancelLabel: 'Keep access',
                confirmLabel: 'Revoke access',
                processingLabel: 'Revoking…',
            };
    }
}
