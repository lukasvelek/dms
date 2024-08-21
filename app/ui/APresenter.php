<?php

namespace App\UI;

abstract class APresenter implements IUIRenderable {
    private ?string $action;
    private string $name;
    private bool $isAjax;

    protected function __construct(string $name) {
        $this->name = $name;

        $this->action = null;

        $this->isAjax = false;
    }

    public function setAction(string $action) {
        $this->action = $action;
    }

    public function setAjax() {
        $this->isAjax = true;
    }

    public function render() {
        // do - handle - render
        if($this->isAjax) {
            $doAction = 'do' . ucfirst($this->action);

            $response = $this->$doAction();

            return $this->createAjaxResponse($response);
        } else {
            $handleAction = 'handle' . ucfirst($this->action);
            if(method_exists($this, $handleAction)) {
                $this->$handleAction();
            }

            $renderAction = 'render' . ucfirst($this->action);
            if(method_exists($this, $renderAction)) {
                return $this->$renderAction();
            }
        }
    }

    private function createAjaxResponse(array $response) {
        return json_encode($response);
    }
}

?>