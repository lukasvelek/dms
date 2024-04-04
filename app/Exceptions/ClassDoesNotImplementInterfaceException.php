<?php

namespace DMS\Exceptions;

use DMS\Constants\ExceptionCodes;
use Throwable;

class ClassDoesNotImplementInterfaceException extends AException {
    public function __construct(string $className, string $interfaceName, string $message = '', Throwable|null $previous = null) {
        $message = 'Class \'' . $className . '\' does not implement interface \'' . $interfaceName . '\'. ' . $message;
        parent::__construct($message, ExceptionCodes::CLASS_DOES_NOT_IMPLEMENT_INTERFACE_EXCEPTION, $previous);
    }
}

?>