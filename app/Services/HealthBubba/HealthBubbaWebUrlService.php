<?php

namespace App\Services\HealthBubba;

use LogicException;

final class HealthBubbaWebUrlService
{
    public function patientAuthentication(bool $accountExists): string
    {
        $baseUrl = rtrim((string) config('services.healthbubba.web_url'), '/');

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new LogicException('The HealthBubba web URL is not configured correctly.');
        }

        return $baseUrl.($accountExists ? '/login' : '/register');
    }
}
