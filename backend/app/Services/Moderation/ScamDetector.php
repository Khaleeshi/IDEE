<?php

namespace App\Services\Moderation;

use Illuminate\Support\Str;

/**
 * Alerte automatique quand un message parle d'argent à transférer, d'un code reçu
 * par SMS ou de coordonnées bancaires (section 8). Elle prévient la personne qui le
 * reçoit ; elle ne bloque rien et ne remplace pas le signalement.
 */
class ScamDetector
{
    public function isSuspicious(?string $text): bool
    {
        if (blank($text)) {
            return false;
        }

        // « Envoie-moi », « envoie moi » et « Envoie   MOI » se lisent de la même façon.
        $normalized = (string) preg_replace('/[\s\-]+/', ' ', Str::lower(Str::ascii($text)));

        foreach (config('idee.scam_alert') as $phrase) {
            if (preg_match('/\b'.preg_quote($phrase, '/').'\b/u', $normalized) === 1) {
                return true;
            }
        }

        return false;
    }
}
