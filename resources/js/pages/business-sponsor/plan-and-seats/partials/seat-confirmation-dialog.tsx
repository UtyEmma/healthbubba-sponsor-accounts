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

export function SeatConfirmationDialog({
    open,
    seats,
    onOpenChange,
    onConfirm,
}: {
    open: boolean;
    seats: number;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[358px]">
                <DialogHeader className="gap-2 px-6 pt-6 pb-4">
                    <DialogTitle className="text-base leading-5 font-semibold">
                        Are you sure you want to continue to adding {seats} more{' '}
                        {seats === 1 ? 'seat' : 'seats'}?
                    </DialogTitle>
                    <DialogDescription className="leading-[18px]">
                        You're charged a pro-rated amount up to your renewal
                        date, and the full monthly allocation is injected
                        immediately.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="flex-row justify-end px-6 pb-6">
                    <DialogClose
                        render={
                            <Button
                                type="button"
                                variant="outline"
                                size="compact"
                            />
                        }
                    >
                        Close
                    </DialogClose>
                    <Button type="button" size="compact" onClick={onConfirm}>
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
