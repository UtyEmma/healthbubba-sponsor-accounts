<?php

namespace App\Models;

use App\Concerns\HasWallet;
use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;
use Revoltify\Subscriptionify\Models\Feature;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $logo
 * @property AccountTypes $type
 */
class Workspace extends Model implements Subscribable
{
    use HasWallet, InteractsWithSubscriptions;

    protected $fillable = ['name', 'type', 'logo', 'description'];

    protected $casts = [
        'type' => AccountTypes::class,
    ];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<PaymentMethod, $this> */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /** @return MorphMany<Transaction, $this> */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    public static function current(): ?self
    {
        $user = Auth::user();

        return $user?->workspace;
    }

    public static function isCurrent(Workspace $workspace): bool
    {
        return $workspace->is(self::current());
    }

    public function provider(): string
    {
        return $this->type->provider();
    }

    /** @return EloquentCollection<int, Plan> */
    public function plans(): EloquentCollection
    {
        return Plan::with('features')->whereAccountType($this->type)->get();
    }

    /** @return Collection<int, Feature> */
    public function features(): Collection
    {
        return $this->plans()
            ->flatMap(fn (Plan $plan): EloquentCollection => $plan->features)
            ->unique('id')
            ->values();
    }
}
