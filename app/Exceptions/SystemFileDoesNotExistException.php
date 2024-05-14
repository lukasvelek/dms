<?php

namespace DMS\Exceptions;

use DMS\Constants\ExceptionCodes;
use Throwable;

class SystemFileDoesNotExistException extends AException {
    public function __construct(string $filename, string $message = '', Throwable|null $previous = null) {
        $message = 'File \'' . $filename . '\' has not been found. ' . $message;

        parent::__construct($message, ExceptionCodes::SYSTEM_FILE_DOES_NOT_EXIST_EXCEPTION, $previous);
    }
}

?>