<?php

namespace DMS\Modules;

use DMS\Core\TemplateManager;

abstract class APresenter implements IPresenter {
    private string $name;
    private string $title;
    private IModule $module;
    private array $actions;

    protected TemplateManager $templateManager;
    
    public string $subpanel = '';
    public bool $drawSubpanel = false;

    protected function __construct(string $name, string $title = '') {
        $this->name = $name;

        if($title == '') {
            $this->title = $this->name;
        } else {
            $this->title = $title;
        }

        $this->templateManager = TemplateManager::getTemporaryObject();

        $this->actions = [];
    }

    public function getActions() {
        return $this->actions;
    }

    public function getModule() {
        return $this->module;
    }

    public function setModule(IModule $module) {
        $this->module = $module;
    }

    public function getName() {
        return $this->name;
    }

    public function getTitle() {
        return $this->title;
    }

    public function performAction(string $name) {
        if(method_exists($this, $name)) {
            return $this->$name();
        } else {
            die('Method does not exist!');
        }
    }

    protected function setActions(array $actions) {
        $this->actions = $actions;
    }

    protected function getActionNamesFromClass(object $class, bool $save = true) {
        $methods = get_class_methods($class);

        $tempMethods = [];
        foreach($methods as $method) {
            if(str_contains($method, 'show')) {
                $tempMethods[$method] = substr($method, 4);
            }
        }

        if($save) {
            $this->actions = $tempMethods;
            return null;
        } else {
            return $tempMethods;
        }
    }
}

?>