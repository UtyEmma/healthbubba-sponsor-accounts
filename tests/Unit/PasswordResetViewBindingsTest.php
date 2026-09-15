<?php

use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse;
use Laravel\Fortify\Http\Responses\SimpleViewResponse;
use Tests\TestCase;

uses(TestCase::class);

it('binds the password reset view responses', function () {
    expect(app(RequestPasswordResetLinkViewResponse::class))
        ->toBeInstanceOf(SimpleViewResponse::class)
        ->and(app(ResetPasswordViewResponse::class))
        ->toBeInstanceOf(SimpleViewResponse::class);
});
