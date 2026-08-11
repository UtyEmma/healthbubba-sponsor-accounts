import { CircleAlertIcon, CircleCheckIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

export function PaymentStatusNotice({
    success,
    error,
}: {
    success?: string | null;
    error?: string | null;
}) {
    const message = error ?? success;

    if (!message) {
        return null;
    }

    const isError = Boolean(error);
    const Icon = isError ? CircleAlertIcon : CircleCheckIcon;

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={cn(
                'mt-6 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm',
                isError
                    ? 'border-destructive/20 bg-destructive/5 text-destructive'
                    : 'border-success/20 bg-success/5 text-success',
            )}
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p>{message}</p>
        </div>
    );
}
