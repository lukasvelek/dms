<?php

namespace App\Exceptions\Core;

use App\Exceptions\AException;
use Throwable;

class UndefinedModuleException extends AException {
    public function __construct(string $moduleName, ?Throwable $previous = null) {
        $message = 'Undefined module \'' . $moduleName . '\'';

        parent::__construct('UndefinedModuleException', $message, true, $previous);
    }
}

?>