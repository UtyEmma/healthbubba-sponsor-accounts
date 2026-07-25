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

it('renders the business sponsor dashboard', function () {
    $this->get(route('business.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('business-sponsor/dashboard'));
});

it('renders the business sponsor consultation reports page', function () {
    $this->get(route('business.reports'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('business-sponsor/consultations/index'));
});

it('renders the business sponsor employees page', function () {
    $this->get(route('business.employees'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('business-sponsor/employees/index'));
});

it('renders the business sponsor plan and seats page', function () {
    $this->get(route('business.plans'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('business-sponsor/plan-and-seats/index'));
});

it('renders the institutional sponsor dashboard', function () {
    $this->get(route('institutional.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/dashboard'));
});

it('renders the institutional sponsor consultations page', function () {
    $this->get(route('institutional.consultations'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/consultations/index'));
});

it('renders the institutional sponsor notifications page', function () {
    $this->get(route('institutional.notifications'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/notifications/index'));
});

it('renders the institutional sponsor coverage page', function () {
    $this->get(route('institutional.coverage'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/coverage/index'));
});

it('renders the institutional sponsor enrollment codes page', function () {
    $this->get(route('institutional.enrollment_codes'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/enrollment-codes/index'));
});

it('renders the institutional sponsor reports page', function () {
    $this->get(route('institutional.reports'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/reports/index'));
});

it('renders the institutional sponsor team page', function () {
    $this->get(route('institutional.team'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('institutional-sponsor/team/index'));
});
