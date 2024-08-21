<?php

namespace App\Exceptions\Core;

use App\Exceptions\AException;
use Throwable;

class UndefinedPresenterException extends AException {
    public function __construct(string $presenterName, string $moduleName, ?Throwable $previous = null) {
        $message = 'Undefined presenter \'' . $presenterName . '\' in module \'' . $moduleName . '\'.';

        parent::__construct('UndefinedPresenterException', $message, true, $previous);
    }
}

?>