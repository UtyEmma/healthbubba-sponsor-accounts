import type { ReactNode } from 'react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export function SettingsSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="pt-6">
            <Card className="gap-0 bg-muted/30 py-0 shadow-none">
                <CardHeader className="gap-1 px-5 py-3">
                    <CardTitle className="text-sm font-medium">
                        {title}
                    </CardTitle>
                    <CardDescription className="pt-1 text-[13px] leading-[18px]">
                        {description}
                    </CardDescription>
                </CardHeader>
                <CardContent className="rounded-xl border bg-card p-4 shadow-control">
                    {children}
                </CardContent>
            </Card>
        </section>
    );
}
