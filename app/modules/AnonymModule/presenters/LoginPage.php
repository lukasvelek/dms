<?php

namespace DMS\Modules\AnonymModule;

use DMS\Modules\IPresenter;

class LoginPage implements IPresenter {
    /**
     * @var string
     */
    private $name;

    public function __construct() {
        $this->name = 'LoginPage';
    }

    public function getName() {
        return $this->name;
    }

    public function performAction(string $name) {
        if(method_exists($this, $name)) {
            return $this->$name();
        } else {
            die('Method does not exist!');
        }
    }

    public function showForm() {
        $text = '<p>Test</p>';

        return $text;
    }
}

?>