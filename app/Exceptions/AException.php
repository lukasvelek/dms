<?php

namespace DMS\Exceptions;

use Throwable;

abstract class AException extends \Exception {
    protected function __construct(string $message, int $code = 0, Throwable|null $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

?>