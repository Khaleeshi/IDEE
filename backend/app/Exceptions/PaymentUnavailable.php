<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le prestataire de paiement n'a pas pu traiter la demande.
 */
class PaymentUnavailable extends RuntimeException {}
