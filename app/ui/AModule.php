<?php

namespace App\UI;

abstract class AModule implements IUIRenderable {
    private APresenter $presenter;
    public string $name;
    public array $cfg;

    protected function __construct(string $name) {
        $this->name = $name;
        $this->cfg = [];
    }

    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    public function render() {
        return $this->presenter->render();
    }

    private function prepareTemplate() {

    }
}

?>