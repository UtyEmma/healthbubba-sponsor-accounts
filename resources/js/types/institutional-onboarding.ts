import type { Campaign } from './campaign';
import type { Workspace } from './workspace';

export interface InstitutionalOrganizationPageProps {
    organization: Workspace;
}

export interface InstitutionalSupportPageProps {
    organization: Workspace;
    campaign: Campaign | null;
    supportEmail: string;
    supportMailtoUrl: string;
}
