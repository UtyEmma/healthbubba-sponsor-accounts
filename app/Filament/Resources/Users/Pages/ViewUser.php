<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Account\Roles;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('toggleEmailVerification')
                ->label(fn (User $record): string => $record->email_verified_at === null
                    ? 'Verify email'
                    : 'Remove email verification')
                ->requiresConfirmation()
                ->visible(fn (User $record): bool => $this->canManageVerification($record))
                ->action(fn (User $record): bool => $this->toggleVerification($record, 'email_verified_at')),
            Action::make('togglePhoneVerification')
                ->label(fn (User $record): string => $record->phone_verified_at === null
                    ? 'Verify phone'
                    : 'Remove phone verification')
                ->requiresConfirmation()
                ->visible(fn (User $record): bool => $this->canManageVerification($record))
                ->action(fn (User $record): bool => $this->toggleVerification($record, 'phone_verified_at')),
            Action::make('toggleAccountVerification')
                ->label(fn (User $record): string => $record->account_verified_at === null
                    ? 'Verify account'
                    : 'Remove account verification')
                ->requiresConfirmation()
                ->visible(fn (User $record): bool => $this->canManageVerification($record))
                ->action(fn (User $record): bool => $this->toggleVerification($record, 'account_verified_at')),
        ];
    }

    private function canManageVerification(User $record): bool
    {
        $administrator = auth()->user();

        return $administrator instanceof User
            && ($record->role === Roles::USER || $administrator->isSuperAdmin());
    }

    private function toggleVerification(User $record, string $attribute): bool
    {
        abort_unless($this->canManageVerification($record), 403);

        return $record->update([
            $attribute => $record->getAttribute($attribute) === null ? now() : null,
        ]);
    }
}
