<?php

use App\Http\Controllers\Api\Consultations\CancelConsultationReservationController;
use App\Http\Controllers\Api\Consultations\CheckConsultationEligibilityController;
use App\Http\Controllers\Api\Consultations\RecordConsultationUsageController;
use App\Http\Controllers\Api\Consultations\StoreConsultationReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['healthbubba.service', 'throttle:120,1'])
    ->name('api.')
    ->group(function (): void {
        Route::post('/consultation-usages', RecordConsultationUsageController::class)
            ->name('consultation_usages.store');
        Route::post('/sponsors', CheckConsultationEligibilityController::class)
            ->name('sponsor_eligibility.show');
        Route::post('/consultation-reservations', StoreConsultationReservationController::class)
            ->name('consultation_reservations.store');
        Route::delete(
            '/consultation-reservations/{appointment:appointment_id}',
            CancelConsultationReservationController::class,
        )->name('consultation_reservations.cancel');
    });
