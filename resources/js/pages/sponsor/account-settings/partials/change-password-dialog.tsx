import { CheckCircleIcon, XCircleIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const requirements = [
    {
        label: 'Password should be minimum 12 characters',
        test: (value: string) => value.length >= 12,
    },
    {
        label: 'Include at least one uppercase letter',
        test: (value: string) => /[A-Z]/.test(value),
    },
    {
        label: 'Include at least one number',
        test: (value: string) => /\d/.test(value),
    },
    {
        label: 'Include at least one special character',
        test: (value: string) => /[^A-Za-z0-9]/.test(value),
    },
];

export function ChangePasswordDialog({ onChanged }: { onChanged: () => void }) {
    const [open, setOpen] = useState(false);
    const [newPassword, setNewPassword] = useState('');
    const isValid = requirements.every(({ test }) => test(newPassword));

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!isValid) {
            return;
        }

        onChanged();
        setNewPassword('');
        setOpen(false);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={<Button variant="outline" size="compact" />}>
                Change Password
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Change Password
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Set a unique password for your account
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit}>
                    <div className="grid gap-4 px-6 py-4">
                        <PasswordField
                            label="Current Password"
                            name="currentPassword"
                        />
                        <PasswordField
                            label="New Password"
                            name="newPassword"
                            value={newPassword}
                            onChange={(event) =>
                                setNewPassword(event.target.value)
                            }
                        />
                        <ul
                            className="grid gap-2"
                            aria-label="Password requirements"
                        >
                            {requirements.map(({ label, test }) => {
                                const passes = test(newPassword);

                                return (
                                    <li
                                        key={label}
                                        className="flex items-center gap-2 text-[13px] text-muted-foreground"
                                    >
                                        {passes ? (
                                            <CheckCircleIcon className="size-3.5 text-success" />
                                        ) : (
                                            <XCircleIcon className="size-3.5 text-destructive" />
                                        )}
                                        {label}
                                    </li>
                                );
                            })}
                        </ul>
                    </div>

                    <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                        <DialogClose
                            render={
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="compact"
                                />
                            }
                        >
                            Cancel
                        </DialogClose>
                        <Button
                            type="submit"
                            size="compact"
                            disabled={!isValid}
                        >
                            Continue
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function PasswordField({
    label,
    ...props
}: React.ComponentProps<typeof Input> & { label: string }) {
    return (
        <label className="grid gap-1.5 text-sm font-medium">
            {label}
            <Input type="password" required className="h-10" {...props} />
        </label>
    );
}
