<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsuranceController;
use App\Notifications\SyncErrorNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification; // Ajoute cette ligne

Route::get('/', [InsuranceController::class, 'index'])->name('insurances.index');
Route::post('/insurances/import', [InsuranceController::class, 'import'])->name('insurances.import');
Route::get('/insurances/export', [InsuranceController::class, 'export'])->name('insurances.export');
Route::get('/insurances/export-single/{id}', [InsuranceController::class, 'exportSingle'])->name('insurances.exportSingle');
Route::get('/insurances/sync', [InsuranceController::class, 'sync'])->name('insurances.sync');

Route::get('/test-mail', function () {
    Log::debug('Test d\'envoi de mail');

    try {
        Notification::route('mail', 'ruthboko23@gmail.com')
            ->notify(new SyncErrorNotification('Message de test'));

        return 'Test d\'email initié - Vérifiez les logs';
    } catch (\Exception $e) {
        Log::error('Échec du test mail', [
            'erreur' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return 'Échec du test d\'email : ' . $e->getMessage();
    }
});
