<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le code de connexion n'a pas pu être remis par ce canal.
 */
class CodeDeliveryFailed extends RuntimeException {}
