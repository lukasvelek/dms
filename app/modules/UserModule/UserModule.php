<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\IModule;
use DMS\Modules\IPresenter;

class UserModule implements IModule {
    /**
     * @var IPresenter
     */
    public $currentPresenter;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array<IPresenters>
     */
    private $presenters;

    public function __construct() {
        $this->name = 'UserModule';
    }

    public function getName() {
        return $this->name;
    }

    public function getPresenterByName(string $name)
    {
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