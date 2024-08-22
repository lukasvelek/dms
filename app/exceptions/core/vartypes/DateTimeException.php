<?php

namespace App\Exceptions\Core\VarTypes;

use App\Exceptions\AException;

class DateTimeException extends AException {
    public function __construct(string $text) {
        parent::__construct('DateTimeException', $text);
    }
}

?>