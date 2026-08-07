<?php

namespace App\Models;

use App\Concerns\HasWallet;
use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;

class Workspace extends Model implements Subscribable {
    use HasWallet, InteractsWithSubscriptions;

    protected $fillable = ['name', 'type', 'logo', 'description'];

    protected $casts = [
        'type' => AccountTypes::class,
    ];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    static function current(): self {
        $user = Auth::user();
        return $user->workspace;
    }

    static function isCurrent(Workspace $workspace): bool {
        return $workspace->is(self::current());
    }

    
}
