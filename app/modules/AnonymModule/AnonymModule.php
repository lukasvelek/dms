<?php

namespace DMS\Modules\AnonymModule;

use \DMS\Modules\IModule;
use \DMS\Modules\IPresenter;

class AnonymModule implements IModule {
    /**
     * @var IPresenter
     */
    public $currentPresenter;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var array<IPresenter>
     */
    private $presenters;

    /**
     * @var array<object>
     */
    private $components;

    public function __construct() {
        $this->name = 'AnonymModule';
        $this->components = array();
    }

    public function getName() {
        return $this->name;
    }

    public function getPresenterByName(string $name) {
        if(array_key_exists($name, $this->presenters)) {
            return $this->presenters[$name];
        }
    }

    public function setPresenter(IPresenter $presenter) {
        $this->currentPresenter = $presenter;
    }

    public function registerPresenter(IPresenter $presenter) {
        $presenter->setModule($this);
        $this->presenters[$presenter->getName()] = $presenter;
    }

    public function addComponent(string $name, object $object) {
        $this->components[$name] = $object;
    }

    public function getComponent(string $name) {
        if(array_key_exists($name, $this->components)) {
            return $this->components[$name];
        } else {
            return false;
        }
    }
}

?>