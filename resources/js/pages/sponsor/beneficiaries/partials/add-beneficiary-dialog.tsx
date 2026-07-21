import { PlusIcon } from 'lucide-react';
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

export type BeneficiaryFormData = {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
};

export function AddBeneficiaryDialog({
    onAdd,
}: {
    onAdd: (beneficiary: BeneficiaryFormData) => void;
}) {
    const [open, setOpen] = useState(false);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        onAdd({
            firstName: String(form.get('firstName')),
            lastName: String(form.get('lastName')),
            email: String(form.get('email')),
            phone: String(form.get('phone')),
        });
        setOpen(false);
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger
                render={
                    <Button
                        size="compact"
                        className="self-start sm:self-auto"
                    />
                }
            >
                <PlusIcon className="size-4" />
                Add beneficiary
            </DialogTrigger>
            <DialogContent showCloseButton={false}>
                <DialogHeader className="px-6 pt-6 border-b pb-2">
                    <DialogTitle className="text-base leading-[21px] font-semibold">
                        Add a beneficiary
                    </DialogTitle>
                    <DialogDescription className="max-w-[392px] pt-1 text-sm leading-5">
                        Invite someone to access healthcare through your
                        sponsorship. They’ll receive an email to set up their
                        account.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit}>
                    <div className="grid gap-4 px-6 py-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                label="First name"
                                name="firstName"
                                placeholder="e.g. Chidi"
                            />
                            <FormField
                                label="Last name"
                                name="lastName"
                                placeholder="e.g. Okafor"
                            />
                        </div>
                        <FormField
                            label="Email address"
                            name="email"
                            type="email"
                            placeholder="chidi@example.com"
                        />
                        <FormField
                            label="Phone number"
                            name="phone"
                            type="tel"
                            placeholder="+234 803 444 5566"
                        />
                    </div>

                    <DialogFooter className="flex-row items-center border-t justify-between gap-3 px-6 py-3">
                        {/* <div className="hidden items-center gap-3 sm:flex">
                            <Button type="reset" variant="ghost" size="compact">
                                Clear
                            </Button>
                            
                        </div> */}
                        <div className="ml-auto flex items-center gap-3">
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
                            <Button type="submit" size="compact">
                                Send invitation
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function FormField({
    label,
    name,
    type = 'text',
    placeholder,
}: {
    label: string;
    name: string;
    type?: string;
    placeholder: string;
}) {
    return (
        <label className="grid gap-1.5 text-[13px] leading-[18px] font-medium">
            {label}
            <Input
                name={name}
                type={type}
                placeholder={placeholder}
                required
                className="h-10"
            />
        </label>
    );
}
