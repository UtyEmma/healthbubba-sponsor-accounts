<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'auth/login')->name('home');

Route::inertia('/login', 'auth/login')->name('login');
Route::inertia('/getting-started', 'sponsor/empty-state')->name('sponsor.getting_started');
Route::inertia('/dashboard', 'sponsor/dashboard')->name('dashboard');
Route::inertia('/beneficiaries', 'sponsor/beneficiaries/index')->name('beneficiaries.index');
Route::inertia('/consultations', 'sponsor/consultations/index')->name('consultations.index');
Route::inertia('/medical-access', 'sponsor/medical-access/index')->name('medical_access.index');
Route::inertia('/wallet', 'sponsor/wallet/index')->name('wallet.index');
Route::inertia('/plan-and-billing', 'sponsor/plan-and-billing/index')->name('plans.index');
Route::inertia('/account-settings', 'sponsor/account-settings/index')->name('account_settings.index');
Route::inertia('/activity-log', 'sponsor/activity-log/index')->name('activity_log.index');
