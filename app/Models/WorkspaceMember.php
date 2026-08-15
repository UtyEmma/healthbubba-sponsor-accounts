<?php

namespace App\Models;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $workspace_id
 * @property int|null $user_id
 * @property int|null $invited_by_user_id
 * @property string $name
 * @property string $email
 * @property WorkspaceMemberRole $role
 * @property WorkspaceMemberStatus $status
 * @property int $invitation_version
 * @property Carbon|null $invited_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $disabled_at
 * @property Carbon|null $last_selected_at
 * @property-read Workspace $workspace
 * @property-read User|null $user
 */
final class WorkspaceMember extends Model
{
    protected $table = 'user_workspace';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'workspace_id', 'user_id', 'invited_by_user_id', 'name', 'email',
        'role', 'status', 'invitation_version', 'invited_at', 'expires_at', 'accepted_at',
        'declined_at', 'cancelled_at', 'disabled_at', 'last_selected_at',
    ];

    protected $attributes = [
        'role' => WorkspaceMemberRole::Viewer,
        'status' => WorkspaceMemberStatus::Invited,
        'invitation_version' => 1,
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * @param  Builder<WorkspaceMember>  $query
     * @return Builder<WorkspaceMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', WorkspaceMemberStatus::Active);
    }

    public function isOwner(): bool
    {
        return $this->role === WorkspaceMemberRole::Owner;
    }

    public function isInvited(): bool
    {
        return $this->status === WorkspaceMemberStatus::Invited;
    }

    public function hasExpired(): bool
    {
        return $this->isInvited() && $this->expires_at?->isPast() === true;
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceMemberRole::class,
            'status' => WorkspaceMemberStatus::class,
            'invitation_version' => 'integer',
            'invited_at' => 'datetime', 'expires_at' => 'datetime', 'accepted_at' => 'datetime',
            'declined_at' => 'datetime', 'cancelled_at' => 'datetime', 'disabled_at' => 'datetime',
            'last_selected_at' => 'datetime',
        ];
    }
}
