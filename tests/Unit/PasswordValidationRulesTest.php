<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

uses(TestCase::class);

it('requires at least six password characters without composition rules', function (): void {
    $validate = static fn (string $password): bool => Validator::make(
        ['password' => $password],
        ['password' => ['required', 'string', Password::defaults()]],
    )->passes();

    expect($validate('abcde'))->toBeFalse()
        ->and($validate('simple'))->toBeTrue();
});
