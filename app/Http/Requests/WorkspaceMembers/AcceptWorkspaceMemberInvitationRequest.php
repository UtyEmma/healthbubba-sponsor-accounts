<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class AcceptWorkspaceMemberInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $member = $this->route('workspaceMember');
        $existing = $member instanceof WorkspaceMember
            && User::query()->where('email', $member->email)->exists();

        return [
            'password' => $existing
                ? ['prohibited']
                : ['required', 'confirmed', Password::defaults()],
        ];
    }
}
