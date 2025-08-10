<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\PayScheduleSettingController;
use App\Http\Controllers\Settings\DeductionTaxSettingController;
use App\Http\Controllers\Settings\AllowanceBonusSettingController;
use App\Http\Controllers\Settings\PaidLeaveSettingController;
use App\Http\Controllers\Settings\HolidaySettingController;
use App\Http\Controllers\Settings\NoWorkSuspendedSettingController;
use App\Http\Controllers\Settings\EmployeeSettingController;

Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
    
    // Employee Settings
    Route::get('employee', [EmployeeSettingController::class, 'index'])->name('employee.index');
    Route::post('employee', [EmployeeSettingController::class, 'update'])->name('employee.update');
    Route::post('employee/reset', [EmployeeSettingController::class, 'reset'])->name('employee.reset');
    Route::get('employee/next-number', [EmployeeSettingController::class, 'getNextEmployeeNumber'])->name('employee.next-number');
    
    // Pay Schedule Settings
    Route::get('pay-schedules', [PayScheduleSettingController::class, 'index'])->name('pay-schedules.index');
    Route::get('pay-schedules/{paySchedule}', [PayScheduleSettingController::class, 'show'])->name('pay-schedules.show');
    Route::get('pay-schedules/{paySchedule}/edit', [PayScheduleSettingController::class, 'edit'])->name('pay-schedules.edit');
    Route::put('pay-schedules/{paySchedule}', [PayScheduleSettingController::class, 'update'])->name('pay-schedules.update');
    Route::patch('pay-schedules/{paySchedule}/toggle', [PayScheduleSettingController::class, 'toggle'])
        ->name('pay-schedules.toggle');

    // Deduction/Tax Settings
    Route::resource('deductions', DeductionTaxSettingController::class);
    Route::patch('deductions/{deduction}/toggle', [DeductionTaxSettingController::class, 'toggle'])
        ->name('deductions.toggle');
    Route::post('deductions/calculate-preview', [DeductionTaxSettingController::class, 'calculatePreview'])
        ->name('deductions.calculate-preview');

    // Allowance/Bonus Settings
    Route::resource('allowances', AllowanceBonusSettingController::class);
    Route::patch('allowances/{allowance}/toggle', [AllowanceBonusSettingController::class, 'toggle'])
        ->name('allowances.toggle');
    Route::post('allowances/calculate-preview', [AllowanceBonusSettingController::class, 'calculatePreview'])
        ->name('allowances.calculate-preview');

    // Paid Leave Settings
    Route::resource('leaves', PaidLeaveSettingController::class);
    Route::patch('leaves/{leave}/toggle', [PaidLeaveSettingController::class, 'toggle'])
        ->name('leaves.toggle');
    Route::post('leaves/calculate-preview', [PaidLeaveSettingController::class, 'calculatePreview'])
        ->name('leaves.calculate-preview');

    // Holiday Settings
    Route::resource('holidays', HolidaySettingController::class);
    Route::patch('holidays/{holiday}/toggle', [HolidaySettingController::class, 'toggle'])
        ->name('holidays.toggle');
    Route::get('holidays/filter/year', [HolidaySettingController::class, 'filterByYear'])
        ->name('holidays.filter-year');
    Route::post('holidays/generate-recurring', [HolidaySettingController::class, 'generateRecurring'])
        ->name('holidays.generate-recurring');

    // No Work/Suspended Settings
    Route::resource('no-work', NoWorkSuspendedSettingController::class);
    Route::patch('no-work/{noWork}/activate', [NoWorkSuspendedSettingController::class, 'activate'])
        ->name('no-work.activate');
    Route::patch('no-work/{noWork}/complete', [NoWorkSuspendedSettingController::class, 'complete'])
        ->name('no-work.complete');
    Route::patch('no-work/{noWork}/cancel', [NoWorkSuspendedSettingController::class, 'cancel'])
        ->name('no-work.cancel');
    Route::get('no-work/{noWork}/affected-employees', [NoWorkSuspendedSettingController::class, 'getAffectedEmployees'])
        ->name('no-work.affected-employees');
});
