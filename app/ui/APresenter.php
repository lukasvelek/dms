<?php

namespace App\UI;

abstract class APresenter implements IUIRenderable {
    public string $name;
    private ?string $action;
    private bool $isAjax;
    private ?string $moduleName;

    private TemplateHelper $templateHelper;

    public TemplateEntity $template;

    protected function __construct(string $name) {
        $this->name = $name;

        $this->templateHelper = new TemplateHelper();

        $this->action = null;
        $this->isAjax = false;
        $this->moduleName = null;
    }

    public function getCleanName() {
        return substr($this->name, 0, (strlen($this->name) - strlen('Presenter')));
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
        $this->beforeRender();

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

    private function beforeRender() {
        $this->template = $this->prepareTemplate();
    }

    private function createAjaxResponse(array $response) {
        return json_encode($response);
    }

    private function prepareTemplate() {
        return $this->templateHelper->loadPresenterActionTemplate($this->action, $this->name, $this->moduleName);
    }
}

?>