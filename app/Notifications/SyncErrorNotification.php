<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncErrorNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        try {
            $this->message = $message;
            Log::channel('mail')->debug('Construction de la notification', [
                'message' => $message,
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from_address' => config('mail.from.address')
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Erreur dans le constructeur de la notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function via($notifiable)
    {
        try {
            Log::channel('mail')->debug('Sélection du canal de notification', [
                'notifiable' => $notifiable
            ]);
            return ['mail'];
        } catch (Exception $e) {
            Log::error('Erreur dans la méthode via()', [
                'error' => $e->getMessage()
            ]);
            return ['mail'];
        }
    }

    public function toMail($notifiable)
    {
        try {
            Log::channel('mail')->debug('Préparation du mail', [
                'destinataire' => $notifiable->routes['mail'] ?? 'non défini',
                'message' => $this->message,
                'mail_config' => [
                    'driver' => config('mail.mailer'),
                    'host' => config('mail.host'),
                    'port' => config('mail.port'),
                    'username' => config('mail.username'),
                    'encryption' => config('mail.encryption')
                ]
            ]);

            return (new MailMessage)
                ->subject('Notification de synchronisation des assurances')
                ->line($this->message)
                ->action('Voir les détails', url('/insurances'))
                ->line('Merci d\'utiliser notre application!')
                ->error();
        } catch (\Exception $e) {
            Log::error('Erreur dans toMail()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}
