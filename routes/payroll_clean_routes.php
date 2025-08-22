    // CLEANED PAYROLL MANAGEMENT - Dynamic Draft & Static Processing Flow
    Route::middleware('can:view payrolls')->group(function () {

    // Main payroll routes
    Route::get('payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::get('payrolls/create', [PayrollController::class, 'create'])->name('payrolls.create');
    Route::post('payrolls', [PayrollController::class, 'store'])->name('payrolls.store');
    Route::get('payrolls/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');

    // Draft to Processing flow
    Route::patch('payrolls/{payroll}/submit-to-processing', [PayrollController::class, 'submitToProcessing'])
    ->name('payrolls.submit-to-processing')
    ->middleware('can:edit payrolls');

    // Processing to Approved flow
    Route::patch('payrolls/{payroll}/approve', [PayrollController::class, 'approve'])
    ->name('payrolls.approve')
    ->middleware('can:approve payrolls');

    // Reject processing (back to draft)
    Route::patch('payrolls/{payroll}/reject', [PayrollController::class, 'reject'])
    ->name('payrolls.reject')
    ->middleware('can:edit payrolls');

    // Edit draft payrolls only
    Route::get('payrolls/{payroll}/edit', [PayrollController::class, 'edit'])
    ->name('payrolls.edit')
    ->middleware('can:edit payrolls');
    Route::put('payrolls/{payroll}', [PayrollController::class, 'update'])
    ->name('payrolls.update')
    ->middleware('can:edit payrolls');

    // Payslip generation (approved payrolls only)
    Route::get('payrolls/{payroll}/payslip', [PayrollController::class, 'payslip'])
    ->name('payrolls.payslip');

    // Delete draft payrolls only
    Route::delete('payrolls/{payroll}', [PayrollController::class, 'destroy'])
    ->name('payrolls.destroy')
    ->middleware('can:delete payrolls');

    // Manual refresh of draft calculations (development/debug)
    Route::post('payrolls/{payroll}/refresh-calculations', [PayrollController::class, 'refreshCalculations'])
    ->name('payrolls.refresh-calculations')
    ->middleware('can:edit payrolls');
    });