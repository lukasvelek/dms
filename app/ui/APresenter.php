<?php

namespace App\UI;

abstract class APresenter implements IUIRenderable {
    private ?string $action;
    private string $name;
    private bool $isAjax;
    private ?string $moduleName;

    protected function __construct(string $name) {
        $this->name = $name;

        $this->action = null;
        $this->isAjax = false;
        $this->moduleName = null;
    }

    public function setAction(string $action) {
        $this->action = $action;
    }

    public function setAjax() {
        $this->isAjax = true;
    }

    public function setModuleName(string $name) {
        $this->moduleName = $name;
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
            if(method_exists($this, $renderAction) && file_exists(__DIR__ . '\\' . $this->moduleName . '\\Presenters\\templates\\' . $this->action . '.html')) {
                return $this->$renderAction();
            }
        }
    }

    private function createAjaxResponse(array $response) {
        return json_encode($response);
    }
}

?>