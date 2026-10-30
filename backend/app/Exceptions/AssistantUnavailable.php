<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * L'assistant IA n'a pas pu répondre (service indisponible, refus, réponse incomplète).
 */
class AssistantUnavailable extends RuntimeException {}
