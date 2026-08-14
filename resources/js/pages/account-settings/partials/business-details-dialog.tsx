import { Form } from '@inertiajs/react';
import { useState } from 'react';

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
import { Textarea } from '@/components/ui/textarea';
import accountSettings from '@/routes/account_settings';
import type { Workspace } from '@/types';

export function BusinessDetailsDialog({
    workspace,
    entityLabel,
    onUpdated,
}: {
    workspace: Workspace;
    entityLabel: 'Business' | 'Institution';
    onUpdated: () => void;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={<Button variant="outline" size="compact" />}>
                Edit Details
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Edit {entityLabel} Details
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Update the information shown for this sponsor workspace.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...accountSettings.workspace.update.form()}
                    errorBag="updateWorkspaceDetails"
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    onSuccess={() => {
                        onUpdated();
                        setOpen(false);
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 px-6 py-4">
                                <label className="grid gap-1.5 text-sm font-medium">
                                    {entityLabel} Name
                                    <Input
                                        name="name"
                                        required
                                        defaultValue={workspace.name}
                                        autoComplete="organization"
                                        className="h-10"
                                    />
                                    {errors.name && (
                                        <span className="text-[13px] font-normal text-destructive">
                                            {errors.name}
                                        </span>
                                    )}
                                </label>

                                <label className="grid gap-1.5 text-sm font-medium">
                                    Description
                                    <Textarea
                                        name="description"
                                        defaultValue={
                                            workspace.description ?? ''
                                        }
                                        maxLength={2000}
                                        placeholder={`Add a short description of this ${entityLabel.toLowerCase()}.`}
                                    />
                                    {errors.description && (
                                        <span className="text-[13px] font-normal text-destructive">
                                            {errors.description}
                                        </span>
                                    )}
                                </label>
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
                                <Button
                                    type="submit"
                                    size="compact"
                                    disabled={processing}
                                >
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
