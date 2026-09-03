import { Building2Icon, LandmarkIcon, UserIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import type { AccountType } from '@/types';

export const accountTypeOptions = [
    {
        value: 'individual' as const,
        label: 'Individual Sponsor',
        description: 'Sponsor care for family and loved ones',
        icon: UserIcon,
    },
    {
        value: 'business' as const,
        label: 'Business Sponsor',
        description: 'Provide healthcare for your employees',
        icon: Building2Icon,
    },
    {
        value: 'institution' as const,
        label: 'Institutional Sponsor',
        description: 'Fund healthcare access for communities',
        icon: LandmarkIcon,
    },
];

interface AccountTypeSelectProps {
    value: AccountType | null;
    onChange: (value: AccountType) => void;
    compact?: boolean;
}

export function AccountTypeSelect({
    value,
    onChange,
    compact = false,
}: AccountTypeSelectProps) {
    return (
        <fieldset className={cn('grid gap-3', compact && 'sm:grid-cols-3')}>
            <legend className="sr-only">Account type</legend>
            {accountTypeOptions.map((option) => {
                const Icon = option.icon;
                const selected = value === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={selected}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'flex w-full items-center gap-3 rounded-lg border bg-background p-4 text-left transition-colors',
                            'hover:border-secondary/50 hover:bg-secondary/5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            compact && 'sm:flex-col sm:items-start',
                            selected &&
                                'border-secondary bg-secondary/5 ring-1 ring-secondary',
                        )}
                    >
                        <span className={cn([
                            "flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground",
                            selected && "text-secondary bg-white"
                        ])}>
                            <Icon className="size-5" aria-hidden="true" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className={cn([
                                "block text-sm font-semibold"
                            ])}>
                                {option.label}
                            </span>
                            <span className="block text-xs leading-5 text-muted-foreground">
                                {option.description}
                            </span>
                        </span>
                        <span
                            className={cn(
                                'size-4 shrink-0 rounded-full border',
                                compact && 'sm:sr-only',
                                selected && 'border-4 border-secondary',
                            )}
                            aria-hidden="true"
                        />
                    </button>
                );
            })}
        </fieldset>
    );
}
