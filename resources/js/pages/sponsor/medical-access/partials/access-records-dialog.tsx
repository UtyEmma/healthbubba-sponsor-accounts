import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import type { AccessRequest } from './access-requests-table';

const prescriptions = [
    {
        name: 'Amlodipine 5mg',
        directions: 'Once daily, oral. 30-day supply. 1 refill remaining.',
    },
    {
        name: 'Loratadine 10mg',
        directions: 'Once daily, oral. 30-day supply. 1 refill remaining.',
    },
];

export function AccessRecordsDialog({
    request,
    onOpenChange,
}: {
    request: AccessRequest | null;
    onOpenChange: (open: boolean) => void;
}) {
    const firstName = request?.beneficiary.split(' ')[0] ?? '';

    return (
        <Dialog open={request !== null} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-[528px]">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        {request?.beneficiary} — {request?.dataType}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Read-only access granted by {firstName}. Scoped to this
                        data type only.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-3 px-6 py-4">
                    {prescriptions.map((prescription) => (
                        <Card key={prescription.name} className="shadow-xs border">
                            <CardContent className="space-y-1 p-4">
                                <h3 className="text-sm font-medium">
                                    {prescription.name}
                                </h3>
                                <p className="text-[13px] leading-[18px] text-muted-foreground">
                                    {prescription.directions}
                                </p>
                                <p className="text-xs leading-4 text-muted-foreground">
                                    Prescribed by Dr. A. Bello · 14 Jun 2026
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                    <DialogClose
                        render={<Button variant="outline" size="compact" />}
                    >
                        Close
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
