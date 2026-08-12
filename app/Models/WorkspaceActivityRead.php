<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $notification_id
 * @property int $user_id
 * @property Carbon $read_at
 */
final class WorkspaceActivityRead extends Model
{
    /** @var list<string> */
    protected $fillable = ['notification_id', 'user_id', 'read_at'];

    /** @return BelongsTo<DatabaseNotification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(DatabaseNotification::class, 'notification_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
