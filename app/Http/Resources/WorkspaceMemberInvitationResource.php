<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceMember */
final class WorkspaceMemberInvitationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $existingUser = $this->getRelation('matchedUser');
        $authenticatedUser = $request->user();
        $canAccept = $this->isInvited()
            && ($existingUser === null || ($authenticatedUser instanceof User && $authenticatedUser->is($existingUser)));

        return [
            'name' => $this->name,
            'email' => $this->email,
            'workspaceName' => $this->workspace->name,
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'status' => $this->status->value,
            'expiresAt' => $this->expires_at?->toISOString(),
            'existingAccount' => $existingUser !== null,
            'canAccept' => $canAccept,
            'wrongAccount' => $existingUser !== null
                && $authenticatedUser instanceof User
                && $authenticatedUser->isNot($existingUser),
        ];
    }
}
