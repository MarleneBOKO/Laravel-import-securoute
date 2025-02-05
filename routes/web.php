<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsuranceController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/insurances', [InsuranceController::class, 'index'])->name('insurances.index');
Route::post('/insurances/import', [InsuranceController::class, 'import'])->name('insurances.import');
