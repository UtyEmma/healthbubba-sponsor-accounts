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
import { dashboard } from '@/routes';
import beneficiaries from '@/routes/beneficiaries';

export function PlanSuccessDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="items-center px-6 pt-8 pb-10 text-center">
                    <div className="mb-5 flex size-16 items-center justify-center text-success drop-shadow-md">
                        <CheckIcon className="size-16 stroke-[5]" />
                    </div>
                    <DialogTitle className="text-base leading-6 font-semibold">
                        You’re on Coordinated Care Plan
                    </DialogTitle>
                    <DialogDescription className="max-w-[390px] pt-1 leading-5">
                        Payment successful — ₦59,000/mo. Your upgrade is active
                        right away.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                    <DialogClose
                        render={
                            <Link
                                href={dashboard()}
                                className={buttonVariants({
                                    variant: 'outline',
                                    size: 'compact',
                                })}
                            />
                        }
                    >
                        Go dashboard
                    </DialogClose>
                    <DialogClose
                        render={
                            <Link
                                href={beneficiaries.index()}
                                className={buttonVariants({ size: 'compact' })}
                            />
                        }
                    >
                        Continue to adding beneficiary
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
