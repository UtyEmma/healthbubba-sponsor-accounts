<?php

namespace App\Actions\Marketing;

use App\Models\MarketingReferrer;
use App\Models\User;
use App\Rules\MarketingReferralCode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AttributeSignupReferrerAction
{
    public function execute(User $user, ?string $code): void
    {
        $code = Str::lower(trim($code ?? ''));
        if ($code === '') {
            return;
        }
        Validator::make(['referral_code' => $code], ['referral_code' => ['required', new MarketingReferralCode]])->validate();
        try {
            $referrerId = MarketingReferrer::query()
                ->where(function ($query) use ($code): void {
                    if (Str::isUuid($code)) {
                        $query->where('public_id', $code);
                    } else {
                        $query->whereRaw('LOWER(username) = ?', [$code])
                            ->orWhereRaw('LOWER(referral_code) = ?', [$code])
                            ->orWhereIn('id', DB::connection('marketing_sql')->table('user_referral_codes')->select('user_id')->where('code', $code));
                    }
                })
                ->where('role', 'marketer')->where('status', 'active')->whereNull('deleted_at')->value('public_id');
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages(['referral_code' => 'Referral codes cannot be verified right now. Please try again.']);
        }
        if ($referrerId === null) {
            throw ValidationException::withMessages(['referral_code' => 'Enter a valid referral code from an active marketer.']);
        }
        $code = $referrerId;
        DB::transaction(function () use ($user, $code): void {
            $sponsor = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if ($sponsor->referrer_id === $code) {
                return;
            }
            if ($sponsor->referrer_id !== null) {
                throw ValidationException::withMessages(['referral_code' => 'This sponsor account already has a referrer. Contact support to request a change.']);
            }
            $sponsor->forceFill(['referrer_id' => $code, 'referrer_linked_at' => now()])->save();
            DB::table('marketing_user_attributions')->insert([
                'decision_id' => (string) Str::uuid(), 'sponsor_user_id' => $sponsor->getKey(),
                'previous_referrer_id' => null, 'referrer_id' => $code, 'actor_id' => null,
                'reason' => 'Sponsor supplied a referral code during account registration.', 'created_at' => now(),
            ]);
            $user->forceFill(['referrer_id' => $code, 'referrer_linked_at' => $sponsor->referrer_linked_at]);
        }, 3);
    }
}
