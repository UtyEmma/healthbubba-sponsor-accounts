<?php

namespace App\Models;

use App\Enums\VerificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property VerificationChannel $channel
 * @property string $destination
 * @property string $code_hash
 * @property int $attempts
 * @property Carbon $sent_at
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property-read User $user
 */
final class AccountVerificationChallenge extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'channel',
        'destination',
        'code_hash',
        'attempts',
        'sent_at',
        'expires_at',
        'consumed_at',
    ];

    /** @var list<string> */
    protected $hidden = ['code_hash'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => VerificationChannel::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
