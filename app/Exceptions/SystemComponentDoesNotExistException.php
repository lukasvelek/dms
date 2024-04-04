<?php

namespace DMS\Exceptions;

use DMS\Constants\ExceptionCodes;
use Throwable;

class SystemComponentDoesNotExistException extends AException {
    public function __construct(string $componentName, string $message = '', Throwable|null $previous = null) {
        $message = 'System component \'' . $componentName . '\' does not exist. ' . $message;
        parent::__construct($message, ExceptionCodes::SYSTEM_COMPONENT_DOES_NOT_EXIST_EXCEPTION, $previous);
    }
}

?>