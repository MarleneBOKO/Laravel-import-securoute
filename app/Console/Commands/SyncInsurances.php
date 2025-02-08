<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecurouteApiService;
use Illuminate\Support\Facades\Log;

class SyncInsurances extends Command
{
    protected $signature = 'insurances:sync';
    protected $description = 'Synchroniser les assurances en attente avec Securoute';

    public function handle(SecurouteApiService $apiService)
    {
        $this->info('Démarrage de la synchronisation des assurances...');

        try {
            $result = $apiService->syncPendingInsurances();

            $this->info("Synchronisation terminée:");
            $this->info("Total: {$result['total']}");
            $this->info("Succès: {$result['success']}");
            $this->info("Échecs: {$result['failed']}");
            $this->info("Déjà assurés: {$result['already_insured']}");

            if ($result['failed'] > 0) {
                $this->warn("Des erreurs sont survenues pendant la synchronisation.");
            }

            if ($result['total'] > ($result['success'] + $result['failed'])) {
                $this->warn("La synchronisation n'est pas complète. Veuillez la relancer.");
            }

            Log::info('Synchronisation des assurances terminée', $result);
        } catch (\Exception $e) {
            $this->error('Erreur de synchronisation: ' . $e->getMessage());
            Log::error('Erreur de synchronisation des assurances: ' . $e->getMessage());
        }
    }
}
