import type { SVGProps } from 'react';

export type SidebarIcon = (props: SVGProps<SVGSVGElement>) => React.ReactNode;

const iconProps = {
    'aria-hidden': true,
    fill: 'none',
    focusable: false,
    viewBox: '0 0 18 18',
} as const;

export function DashboardSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M6.75 2.25H3C2.58579 2.25 2.25 2.58579 2.25 3V8.25C2.25 8.66421 2.58579 9 3 9H6.75C7.16421 9 7.5 8.66421 7.5 8.25V3C7.5 2.58579 7.16421 2.25 6.75 2.25Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M15 2.25H11.25C10.8358 2.25 10.5 2.58579 10.5 3V5.25C10.5 5.66421 10.8358 6 11.25 6H15C15.4142 6 15.75 5.66421 15.75 5.25V3C15.75 2.58579 15.4142 2.25 15 2.25Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M15 9H11.25C10.8358 9 10.5 9.33579 10.5 9.75V15C10.5 15.4142 10.8358 15.75 11.25 15.75H15C15.4142 15.75 15.75 15.4142 15.75 15V9.75C15.75 9.33579 15.4142 9 15 9Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M6.75 12H3C2.58579 12 2.25 12.3358 2.25 12.75V15C2.25 15.4142 2.58579 15.75 3 15.75H6.75C7.16421 15.75 7.5 15.4142 7.5 15V12.75C7.5 12.3358 7.16421 12 6.75 12Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function BeneficiariesSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M12 15.75V14.25C12 13.4544 11.6839 12.6913 11.1213 12.1287C10.5587 11.5661 9.79565 11.25 9 11.25H4.5C3.70435 11.25 2.94129 11.5661 2.37868 12.1287C1.81607 12.6913 1.5 13.4544 1.5 14.25V15.75"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M12 2.346C12.6433 2.51278 13.213 2.88845 13.6198 3.41405C14.0265 3.93965 14.2471 4.58542 14.2471 5.25C14.2471 5.91458 14.0265 6.56035 13.6198 7.08595C13.213 7.61155 12.6433 7.98722 12 8.154"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M16.5 15.75V14.25C16.4995 13.5853 16.2783 12.9396 15.871 12.4142C15.4638 11.8889 14.8936 11.5137 14.25 11.3475"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M6.75 8.25C8.40685 8.25 9.75 6.90685 9.75 5.25C9.75 3.59315 8.40685 2.25 6.75 2.25C5.09315 2.25 3.75 3.59315 3.75 5.25C3.75 6.90685 5.09315 8.25 6.75 8.25Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function ConsultationsSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M8.25 1.5V3"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M3.75 1.5V3"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M3.75 2.25H3C2.60218 2.25 2.22064 2.40804 1.93934 2.68934C1.65804 2.97064 1.5 3.35218 1.5 3.75V6.75C1.5 7.94347 1.97411 9.08807 2.81802 9.93198C3.66193 10.7759 4.80653 11.25 6 11.25C7.19347 11.25 8.33807 10.7759 9.18198 9.93198C10.0259 9.08807 10.5 7.94347 10.5 6.75V3.75C10.5 3.35218 10.342 2.97064 10.0607 2.68934C9.77936 2.40804 9.39782 2.25 9 2.25H8.25"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M6 11.25C6 12.4435 6.47411 13.5881 7.31802 14.432C8.16193 15.2759 9.30653 15.75 10.5 15.75C11.6935 15.75 12.8381 15.2759 13.682 14.432C14.5259 13.5881 15 12.4435 15 11.25V9"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M15 9C15.8284 9 16.5 8.32843 16.5 7.5C16.5 6.67157 15.8284 6 15 6C14.1716 6 13.5 6.67157 13.5 7.5C13.5 8.32843 14.1716 9 15 9Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function MedicalAccessSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M15 9.75C15 13.5 12.375 15.375 9.255 16.4625C9.09162 16.5179 8.91415 16.5152 8.7525 16.455C5.625 15.375 3 13.5 3 9.75V4.5C3 4.30109 3.07902 4.11032 3.21967 3.96967C3.36032 3.82902 3.55109 3.75 3.75 3.75C5.25 3.75 7.125 2.85 8.43 1.71C8.58889 1.57425 8.79102 1.49966 9 1.49966C9.20898 1.49966 9.41111 1.57425 9.57 1.71C10.8825 2.8575 12.75 3.75 14.25 3.75C14.4489 3.75 14.6397 3.82902 14.7803 3.96967C14.921 4.11032 15 4.30109 15 4.5V9.75Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M6.75 9L8.25 10.5L11.25 7.5"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function WalletSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M14.25 5.25V3C14.25 2.80109 14.171 2.61032 14.0303 2.46967C13.8897 2.32902 13.6989 2.25 13.5 2.25H3.75C3.35218 2.25 2.97064 2.40804 2.68934 2.68934C2.40804 2.97064 2.25 3.35218 2.25 3.75C2.25 4.14782 2.40804 4.52936 2.68934 4.81066C2.97064 5.09196 3.35218 5.25 3.75 5.25H15C15.1989 5.25 15.3897 5.32902 15.5303 5.46967C15.671 5.61032 15.75 5.80109 15.75 6V9M15.75 9H13.5C13.1022 9 12.7206 9.15804 12.4393 9.43934C12.158 9.72064 12 10.1022 12 10.5C12 10.8978 12.158 11.2794 12.4393 11.5607C12.7206 11.842 13.1022 12 13.5 12H15.75C15.9489 12 16.1397 11.921 16.2803 11.7803C16.421 11.6397 16.5 11.4489 16.5 11.25V9.75C16.5 9.55109 16.421 9.36032 16.2803 9.21967C16.1397 9.07902 15.9489 9 15.75 9Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M2.25 3.75V14.25C2.25 14.6478 2.40804 15.0294 2.68934 15.3107C2.97064 15.592 3.35218 15.75 3.75 15.75H15C15.1989 15.75 15.3897 15.671 15.5303 15.5303C15.671 15.3897 15.75 15V12"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function PlanBillingSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M15 3.75H3C2.17157 3.75 1.5 4.42157 1.5 5.25V12.75C1.5 13.5784 2.17157 14.25 3 14.25H15C15.8284 14.25 16.5 13.5784 16.5 12.75V5.25C16.5 4.42157 15.8284 3.75 15 3.75Z"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M1.5 7.5H16.5"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function ActivityLogSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M2.25 9C2.25 10.335 2.64588 11.6401 3.38758 12.7501C4.12928 13.8601 5.18349 14.7253 6.41689 15.2362C7.65029 15.7471 9.00749 15.8808 10.3169 15.6203C11.6262 15.3599 12.829 14.717 13.773 13.773C14.717 12.829 15.3599 11.6262 15.6203 10.3169C15.8808 9.00749 15.7471 7.65029 15.2362 6.41689C14.7253 5.18349 13.8601 4.12928 12.7501 3.38758C11.6401 2.64588 10.335 2.25 9 2.25C7.11296 2.2571 5.30173 2.99342 3.945 4.305L2.25 6"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M2.25 2.25V6H6"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
            <path
                d="M9 5.25V9L12 10.5"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.5"
            />
        </svg>
    );
}

export function TeamSidebarIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg {...iconProps} {...props}>
            <path
                d="M6.75 8.25a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M1.5 15.75v-1.5a3 3 0 0 1 3-3H9a3 3 0 0 1 3 3v1.5"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M12 2.35a3 3 0 0 1 0 5.8M16.5 15.75v-1.5a3 3 0 0 0-2.25-2.9"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
