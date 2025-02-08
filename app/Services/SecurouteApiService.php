<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Insurance;
use Carbon\Carbon;
use App\Notifications\SyncErrorNotification;
use Illuminate\Support\Facades\Notification;

class SecurouteApiService
{
    private $baseUrl;
    private $maxRetries = 3;
    private $adminEmail;

    public function __construct()
    {
        $this->baseUrl = config('securoute.base_url');
        $this->adminEmail = 'ruthboko23@gmail.com';
    }

    public function syncPendingInsurances()
    {
        $pendingInsurances = Insurance::whereIn('sync_status', ['pending', 'failed'])->get();

        $results = [
            'total' => $pendingInsurances->count(),
            'success' => 0,
            'failed' => 0,
            'already_insured' => 0
        ];

        $batchSize = 50;
        $timeoutPerBatch = 55;

        foreach ($pendingInsurances->chunk($batchSize) as $batch) {
            $startTime = time();

            foreach ($batch as $insurance) {
                if (time() - $startTime >= $timeoutPerBatch) {
                    $this->notifyRemainingSync($results);
                    break 2;
                }

                $syncResult = $this->syncInsurance($insurance);

                if ($syncResult === 'already_insured') {
                    $results['already_insured']++;
                    $results['success']++;
                } elseif ($syncResult === true) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        if ($pendingInsurances->count() > ($results['success'] + $results['failed'])) {
            $this->notifyRemainingSync($results);
        }

        return $results;
    }

    private function syncInsurance(Insurance $insurance)
    {
        $authData = $this->authenticate();
        if (!$authData) {
            return $this->logSyncFailure($insurance, 'Échec de l\'authentification');
        }

        try {
            $telephone = !empty($insurance->telephone) ? $insurance->telephone : '+229 00 00 00 00';

            $payload = [
                'organization_id' => $authData['organization_id'],
                'immatriculation' => $insurance->immatriculation,
                'echeance' => Carbon::parse($insurance->echeance)->format('Y-m-d'),
                'nom_complet' => $insurance->assure,
                'telephone_de_lassure' => $telephone,
                'user_id' => $authData['user_id'],
                'token' => $authData['token']
            ];

            $response = Http::timeout(10)
                ->withHeaders(['permission' => 'insurance-upload'])
                ->post("{$this->baseUrl}/api/add-single-insurance", $payload);

            $responseData = $response->json();

            // Gestion du cas "déjà assuré"
            if (
                isset($responseData['message']) &&
                strpos($responseData['message'], 'Ce véhicule est déjà assuré jusqu\'à cette date') !== false
            ) {
                $this->updateSyncStatus(
                    $insurance,
                    'synced',
                    'Véhicule déjà assuré',
                    $responseData
                );
                return 'already_insured';
            }

            if (
                $response->successful() &&
                isset($responseData['status']) &&
                $responseData['status'] === "success"
            ) {
                $this->updateSyncStatus(
                    $insurance,
                    'synced',
                    'Synchronisation réussie',
                    $responseData
                );
                return true;
            }

            return $this->logSyncFailure(
                $insurance,
                $responseData['message'] ?? 'Erreur inconnue',
                $responseData
            );

        } catch (\Exception $e) {
            return $this->logSyncFailure($insurance, $e->getMessage());
        }
    }

    private function authenticate($retry = 0)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'permission' => 'insurance-upload',
                    'Accept' => 'application/json'
                ])
                ->post("{$this->baseUrl}/api/get-token", [
                    'email' => config('securoute.email'),
                    'password' => config('securoute.password')
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'token' => $data['data']['user']['token'] ?? null,
                    'organization_id' => $data['data']['user']['organization_id'] ?? null,
                    'user_id' => $data['data']['user']['id'] ?? null
                ];
            }

            throw new \Exception('Échec authentification: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Erreur Securoute Authentication', [
                'message' => $e->getMessage(),
                'retry' => $retry
            ]);

            if ($retry < $this->maxRetries) {
                sleep(2 * ($retry + 1));
                return $this->authenticate($retry + 1);
            }

            $this->notifyCriticalError('Échec répété de l\'authentification: ' . $e->getMessage());
            return null;
        }
    }

    private function updateSyncStatus(Insurance $insurance, $status, $message, $data = null)
    {
        $insurance->update([
            'sync_status' => $status,
            'sync_message' => $message,
            'last_sync_attempt' => now()
        ]);

        Log::info("Sync Success - Insurance {$insurance->id}", [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);

        return true;
    }

    private function logSyncFailure(Insurance $insurance, $message, $data = null)
    {
        Log::error("Sync Failure - Insurance {$insurance->id}", [
            'message' => $message,
            'data' => $data
        ]);

        $insurance->update([
            'sync_status' => 'failed',
            'sync_message' => $message,
            'last_sync_attempt' => now()
        ]);

        return false;
    }

    private function notifyRemainingSync($results)
    {
        try {
            Log::debug('Début notifyRemainingSync avec résultats:', $results);

            $message = "La synchronisation n'est pas complète. Il reste des assurances à synchroniser.\n";
            $message .= "Résultats actuels:\n";
            $message .= "Total: {$results['total']}\n";
            $message .= "Succès: {$results['success']}\n";
            $message .= "Échecs: {$results['failed']}\n";
            $message .= "Déjà assurés: {$results['already_insured']}\n";
            $message .= "\nVeuillez relancer la synchronisation pour traiter les assurances restantes.";

            Log::debug('Configuration mail actuelle:', [
                'driver' => config('mail.driver'),
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'from_address' => config('mail.from.address'),
                'encryption' => config('mail.encryption')
            ]);

            Log::debug('Tentative d\'envoi de notification à:', ['email' => $this->adminEmail]);

            Notification::route('mail', $this->adminEmail)
                ->notify(new SyncErrorNotification($message));

            Log::debug('Notification envoyée avec succès');
        } catch (\Exception $e) {
            Log::error('Exception détaillée lors de l\'envoi de la notification:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function notifyCriticalError($message)
    {
        try {
            Log::debug('Début notifyCriticalError avec message:', ['message' => $message]);

            Log::debug('Configuration mail actuelle:', [
                'driver' => config('mail.driver'),
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'from_address' => config('mail.from.address'),
                'encryption' => config('mail.encryption')
            ]);

            Log::debug('Tentative d\'envoi de notification d\'erreur critique à:', ['email' => $this->adminEmail]);

            Notification::route('mail', $this->adminEmail)
                ->notify(new SyncErrorNotification("Erreur critique: " . $message));

            Log::debug('Notification d\'erreur critique envoyée avec succès');
        } catch (\Exception $e) {
            Log::error('Exception détaillée lors de l\'envoi de la notification d\'erreur critique:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
