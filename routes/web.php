<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsuranceController;

Route::get('/', [InsuranceController::class, 'index'])->name('insurances.index');
Route::post('/insurances/import', [InsuranceController::class, 'import'])->name('insurances.import');
Route::get('/insurances/export', [InsuranceController::class, 'export'])->name('insurances.export');
Route::get('/insurances/export-single/{id}', [InsuranceController::class, 'exportSingle'])->name('insurances.exportSingle');
