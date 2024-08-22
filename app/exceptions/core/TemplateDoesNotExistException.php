<?php

namespace App\Exceptions\Core;

use App\Exceptions\AException;

class TemplateDoesNotExistException extends AException {
    public function __construct(string $templateName) {
        parent::__construct('TemplateDoesNotExistException', 'Template \'' . $templateName . '\' does not exist.');
    }
}

?>