import { Form } from '@inertiajs/react';
import { useState } from 'react';

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
import userProfileInformation from '@/routes/user-profile-information';
import type { User } from '@/types';

export function ProfileInformationDialog({
    user,
    onUpdated,
}: {
    user: User;
    onUpdated: () => void;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={<Button variant="outline" size="compact" />}>
                Edit Profile
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-2">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Edit Personal Information
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Update the name and email address attached to your
                        account.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...userProfileInformation.update.form()}
                    errorBag="updateProfileInformation"
                    options={{ preserveScroll: true }}
                    setDefaultsOnSuccess
                    onSuccess={() => {
                        onUpdated();
                        setOpen(false);
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 px-6 py-4">
                                <ProfileField
                                    label="Full Name"
                                    name="name"
                                    defaultValue={user.name}
                                    autoComplete="name"
                                    error={errors.name}
                                />
                                <ProfileField
                                    label="Email Address"
                                    name="email"
                                    type="email"
                                    defaultValue={user.email}
                                    autoComplete="email"
                                    error={errors.email}
                                />
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
                                    disabled={processing}
                                >
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function ProfileField({
    label,
    error,
    ...props
}: React.ComponentProps<typeof Input> & {
    label: string;
    error?: string;
}) {
    return (
        <label className="grid gap-1.5 text-sm font-medium">
            {label}
            <Input required className="h-10" {...props} />
            {error && (
                <span className="text-[13px] font-normal text-destructive">
                    {error}
                </span>
            )}
        </label>
    );
}
