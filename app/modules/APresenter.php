<?php

namespace DMS\Modules;

abstract class APresenter implements IPresenter {
    public bool $drawSubpanel = false;
    public string $subpanel = '';

    public function performAction(string $name) {
        if(method_exists($this, $name)) {
            return $this->$name();
        } else {
            die('Method does not exist!');
        }
    }
}

?>