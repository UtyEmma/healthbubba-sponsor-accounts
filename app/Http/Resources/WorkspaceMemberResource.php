<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceMember */
final class WorkspaceMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->getRelation('user');

        return [
            'id' => $this->public_id,
            'name' => $user instanceof User ? $user->name : $this->name,
            'email' => $user instanceof User ? $user->email : $this->email,
            'phone' => $user instanceof User ? $user->phone : $this->phone,
            'jobTitle' => $this->job_title,
            'authorizationConfirmedAt' => $this->authorization_confirmed_at?->toISOString(),
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isCurrentUser' => $this->user_id === $request->user()?->getKey(),
            'invitedAt' => $this->invited_at?->toISOString(),
            'expiresAt' => $this->expires_at?->toISOString(),
            'acceptedAt' => $this->accepted_at?->toISOString(),
        ];
    }
}
