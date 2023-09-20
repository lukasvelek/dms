<?php

namespace DMS\Modules\AnonymModule;

use \DMS\Modules\IModule;
use \DMS\Modules\IPresenter;

class AnonymModule implements IModule {
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var array<IPresenter>
     */
    private $presenters;

    /**
     * @var IPresenter
     */
    public $currentPresenter;

    public function __construct() {
        $this->name = 'AnonymModule';
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
        $this->presenters[$presenter->getName()] = $presenter;
    }
}

?>