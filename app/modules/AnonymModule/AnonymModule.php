<?php

namespace DMS\Modules\AnonymModule;

use \DMS\Modules\IModule;
use \DMS\Modules\IPresenter;

class AnonymModule implements IModule {
    public IPresenter $currentPresenter;
    private string $name;
    private string $title;
    private array $presenters;

    public function __construct() {
        $this->name = 'AnonymModule';
        $this->title = 'Anonym Module';
    }

    public function getName() {
        return $this->name;
    }

    public function getTitle() {
        return $this->title;
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

    public function getPresenters() {
        return $this->presenters;
    }
}

?>