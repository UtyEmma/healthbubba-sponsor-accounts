<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

final class MarketingReferralCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Usernames are referral codes; previously shared UUID links remain valid.
        if (! is_string($value) || (! preg_match('/\A[A-Z0-9_]{3,32}\z/i', $value) && ! Str::isUuid($value))) {
            $fail('Enter a username containing 3–32 letters, numbers or underscores.');
        }
    }
}
