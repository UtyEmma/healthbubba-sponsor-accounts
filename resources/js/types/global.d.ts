import type { Auth } from '@/types/auth';
import type { WorkspaceActivitySummary } from './activity';
import type { ActivePaymentGateway } from './payment';
import type { WorkspaceOption, WorkspacePermissions } from './team';
import type { Workspace } from './workspace';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            workspace: Workspace;
            workspaceOptions: WorkspaceOption[];
            workspacePermissions: WorkspacePermissions;
            activityNotifications: WorkspaceActivitySummary | null;
            activePaymentGateway: ActivePaymentGateway | null;
            flash: {
                success: string | null;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
