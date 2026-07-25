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

Route::inertia('/business-sponsor/dashboard', 'business-sponsor/dashboard')->name('business.dashboard');
Route::inertia('/business-sponsor/reports', 'business-sponsor/consultations/index')->name('business.reports');
Route::inertia('/business-sponsor/employees', 'business-sponsor/employees/index')->name('business.employees');
Route::inertia('/business-sponsor/plan-and-seats', 'business-sponsor/plan-and-seats/index')->name('business.plans');

Route::inertia('/institutional-sponsor/dashboard', 'institutional-sponsor/dashboard')->name('institutional.dashboard');
Route::inertia('/institutional-sponsor/consultations', 'institutional-sponsor/consultations/index')->name('institutional.consultations');
Route::inertia('/institutional-sponsor/notifications', 'institutional-sponsor/notifications/index')->name('institutional.notifications');
Route::inertia('/institutional-sponsor/coverage', 'institutional-sponsor/coverage/index')->name('institutional.coverage');
Route::inertia('/institutional-sponsor/enrollment-codes', 'institutional-sponsor/enrollment-codes/index')->name('institutional.enrollment_codes');
Route::inertia('/institutional-sponsor/reports', 'institutional-sponsor/reports/index')->name('institutional.reports');
Route::inertia('/institutional-sponsor/team', 'institutional-sponsor/team/index')->name('institutional.team');
