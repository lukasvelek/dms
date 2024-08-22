<?php

namespace App\UI\ErrorModule\Presenters;

use App\UI\APresenter;

class ErrorPresenter extends APresenter {
    public function __construct() {
        parent::__construct('ErrorPresenter');
    }

    public function handleError() {
        global $app;
    }

    public function renderError() {
        
    }
}

?>