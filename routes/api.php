<?php

use App\Http\Controllers\Api\Consultations\CancelConsultationReservationController;
use App\Http\Controllers\Api\Consultations\CheckConsultationEligibilityController;
use App\Http\Controllers\Api\Consultations\ConfirmConsultationReservationController;
use App\Http\Controllers\Api\Consultations\RecordConsultationUsageController;
use App\Http\Controllers\Api\Consultations\ShowPatientConsultationSponsorshipsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['healthbubba.service', 'throttle:120,1'])
    ->name('api.')
    ->group(function (): void {
        Route::get(
            '/patients/{patient}/consultation-sponsorships',
            ShowPatientConsultationSponsorshipsController::class,
        )->name('patients.consultation_sponsorships.show');
        Route::post('/consultation-usages', RecordConsultationUsageController::class)
            ->name('consultation_usages.store');
        Route::post('/consultation-eligibility', CheckConsultationEligibilityController::class)
            ->name('consultation_eligibility.store');
        Route::post(
            '/consultation-reservations/{consultation:public_id}/confirm',
            ConfirmConsultationReservationController::class,
        )->name('consultation_reservations.confirm');
        Route::delete(
            '/consultation-reservations/{consultation:public_id}',
            CancelConsultationReservationController::class,
        )->name('consultation_reservations.cancel');
    });
