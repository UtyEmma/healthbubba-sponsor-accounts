import { DownloadIcon } from 'lucide-react';

import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const templateUrl = '/templates/beneficiary-upload-template.csv';

export function BeneficiaryImportTemplateLink({
    className,
}: {
    className?: string;
}) {
    return (
        <a
            href={templateUrl}
            download="beneficiary-upload-template.csv"
            className={cn(
                buttonVariants({ variant: 'link', size: 'sm' }),
                'h-auto justify-start gap-1.5 px-0 py-0 text-sm',
                className,
            )}
        >
            <DownloadIcon className="size-4" aria-hidden="true" />
            Download CSV template
        </a>
    );
}
