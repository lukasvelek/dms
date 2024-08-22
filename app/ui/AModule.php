<?php

namespace App\UI;

abstract class AModule implements IUIRenderable {
    private APresenter $presenter;
    private TemplateHelper $templateHelper;

    public string $name;
    public array $cfg;
    public TemplateEntity $template;

    protected function __construct(string $name) {
        $this->name = $name;
        $this->cfg = [];

        $this->templateHelper = new TemplateHelper();
    }

    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    public function getCleanName() {
        return substr($this->name, 0, (strlen($this->name) - strlen('Module')));
    }

    public function render() {
        $this->beforeRender();

        $content = $this->presenter->render();

        $this->template->app_content = $content;

        return $this->template->render();
    }

    private function beforeRender() {
        $this->template = $this->prepareTemplate();

        $this->template->app_title = $this->presenter->getCleanName();
    }

    private function prepareTemplate() {
        return $this->templateHelper->loadModuleTemplate($this->name);
    }
}

?>