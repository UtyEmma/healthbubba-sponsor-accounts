<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the sponsor login page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

it('renders the first-time sponsor empty state', function () {
    $this->get(route('sponsor.getting_started'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/empty-state'));
});

it('renders the sponsor dashboard', function () {
    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/dashboard'));
});

it('renders the sponsor beneficiaries page', function () {
    $this->get(route('beneficiaries.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/beneficiaries/index'));
});

it('renders the sponsor consultations page', function () {
    $this->get(route('consultations.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/consultations/index'));
});

it('renders the sponsor medical access page', function () {
    $this->get(route('medical_access.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/medical-access/index'));
});

it('renders the sponsor wallet page', function () {
    $this->get(route('wallet.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/wallet/index'));
});

it('renders the sponsor plan and billing page', function () {
    $this->get(route('plans.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/plan-and-billing/index'));
});

it('renders the sponsor account settings page', function () {
    $this->get(route('account_settings.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/account-settings/index'));
});

it('renders the sponsor activity log page', function () {
    $this->get(route('activity_log.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('sponsor/activity-log/index'));
});
