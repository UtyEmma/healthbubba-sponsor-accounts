import { Form } from '@inertiajs/react';

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
import { logout } from '@/routes';

export function LogoutConfirmationDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-2 border-b px-6 pt-6 pb-5">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Log Out
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Are you sure you wish to log out?
                    </DialogDescription>
                </DialogHeader>

                <Form {...logout.form()}>
                    {({ processing }) => (
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
                                Cancel
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                size="compact"
                                disabled={processing}
                            >
                                {processing ? 'Logging out...' : 'Log Out'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
