<?php

namespace App\Notifications;

/**
 * Alerte dont l'échec sur WhatsApp déclenche un SMS de secours (NO-03) :
 * celles qui protègent la règle « aucune demande sans réponse ».
 */
interface CriticalAlert {}
