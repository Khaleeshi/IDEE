<?php

namespace App\Notifications;

use App\Enums\VerificationStatus;
use App\Models\Verification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Résultat de l'examen d'une vérification d'identité (CF-02).
 */
class VerificationReviewed extends Notification
{
    use Queueable;

    public function __construct(public readonly Verification $verification) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_id' => $this->verification->id,
            'message' => $this->verification->status === VerificationStatus::Approuvee
                ? 'Votre identité est vérifiée : votre profil affiche désormais vos rôles d\'investisseur ou d\'expert.'
                : 'Votre vérification d\'identité n\'a pas abouti : '.$this->verification->refusal_reason,
        ];
    }
}
