<?php

use Tests\TestCase;

uses(TestCase::class);

it('requires an account type when the other credentials are missing', function (): void {
    $this->from(route('login'))
        ->post(route('login.store'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'account_type' => 'Select the account type you want to sign in to.',
            'email',
            'password',
        ]);
});

it('requires an account type before attempting an unknown email', function (): void {
    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'unknown@example.com',
            'password' => 'unknown-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'account_type' => 'Select the account type you want to sign in to.',
        ]);
});

it('rejects an invalid account type during login request validation', function (): void {
    $this->from(route('login'))
        ->post(route('login.store'), [
            'account_type' => 'invalid-sponsor-type',
            'email' => 'unknown@example.com',
            'password' => 'unknown-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'account_type' => 'Select a valid account type to sign in to.',
        ]);
});
