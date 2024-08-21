<?php

namespace App\Exceptions;

use Exception;
use Throwable;

abstract class AException extends Exception {
    private string $title;

    protected function __construct(string $title, string $message, bool $createFile = true, ?Throwable $previous = null) {
        parent::__construct($message, 9999, $previous);

        $this->title = $title;
    }
}

?>