import { Link } from '@inertiajs/react';
import { CheckIcon } from 'lucide-react';

import { buttonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { home } from '@/routes';

export function PlanSuccessDialog({
    open,
    onOpenChange,
    planName,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    planName: string | null;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="items-center px-6 pt-8 pb-10 text-center">
                    <div className="mb-5 flex size-16 items-center justify-center text-success drop-shadow-md">
                        <CheckIcon className="size-16 stroke-[5]" />
                    </div>
                    <DialogTitle className="text-base leading-6 font-semibold">
                        {planName ?? 'Plan'} selected
                    </DialogTitle>
                    <DialogDescription className="max-w-[390px] pt-1 leading-5">
                        Review and payment confirmation are required before this
                        plan becomes active.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                    <DialogClose
                        render={
                            <Link
                                href={home()}
                                className={buttonVariants({
                                    variant: 'outline',
                                    size: 'compact',
                                })}
                            />
                        }
                    >
                        Go dashboard
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
