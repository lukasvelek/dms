<?php

namespace App\Exceptions\Core;

use App\Exceptions\AException;

class DatabaseConnectionException extends AException {
    public function __construct() {
        parent::__construct('DatabaseConnectionException', 'Could not connect to the database.');
    }
}

?>