<?php

namespace DMS\Exceptions;

use DMS\Constants\ExceptionCodes;
use Throwable;

class ValueIsNullException extends AException {
    public function __construct(string $variableName, string $message = '', Throwable|null $previous = null) {
        $message = 'Value of variable \'' . $variableName . '\' is NULL. ' . $message;
        parent::__construct($message, ExceptionCodes::VALUE_IS_NULL_EXCEPTION, $previous);
    }
}

?>