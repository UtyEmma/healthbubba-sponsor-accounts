import { Head } from '@inertiajs/react';

import { BrandMark } from '@/components/brand-mark';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { UserAccountMenu } from '@/components/user-account-menu';

export default function WorkspaceAccessPage() {
    return (
        <main className="min-h-screen bg-muted/40 px-4 py-8">
            <Head title="Workspace access" />
            <div className="mx-auto flex max-w-2xl items-center justify-between gap-4">
                <BrandMark showName />
                <UserAccountMenu />
            </div>
            <Card className="mx-auto mt-16 max-w-lg text-center shadow-card">
                <CardHeader>
                    <CardTitle>No active workspace access</CardTitle>
                </CardHeader>
                <CardContent className="text-sm leading-6 text-muted-foreground">
                    Your workspace access is currently disabled or unavailable.
                    Contact a workspace owner or administrator to restore
                    access.
                </CardContent>
            </Card>
        </main>
    );
}
