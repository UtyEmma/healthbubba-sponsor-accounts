import { Dialog } from '@base-ui/react/dialog';
import { XIcon } from 'lucide-react';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export const Sheet = Dialog.Root;
export const SheetTrigger = Dialog.Trigger;
export const SheetClose = Dialog.Close;
export const SheetTitle = Dialog.Title;
export const SheetDescription = Dialog.Description;

export function SheetContent({ className, children, ...props }: ComponentProps<typeof Dialog.Popup>) {
    return (
        <Dialog.Portal>
            <Dialog.Backdrop className="fixed inset-0 z-40 bg-black/25 transition-opacity data-[ending-style]:opacity-0 data-[starting-style]:opacity-0 lg:hidden" />
            <Dialog.Viewport className="fixed inset-0 z-50 flex justify-start lg:hidden">
                <Dialog.Popup className={cn('h-full w-64 border-r border-border bg-sidebar shadow-xl transition-transform duration-200 data-[ending-style]:-translate-x-full data-[starting-style]:-translate-x-full', className)} {...props}>
                    {children}
                    <Dialog.Close aria-label="Close navigation" className="absolute top-4 right-3 inline-flex size-8 items-center justify-center rounded-control text-muted-foreground outline-none hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring">
                        <XIcon className="size-5" />
                    </Dialog.Close>
                </Dialog.Popup>
            </Dialog.Viewport>
        </Dialog.Portal>
    );
}
