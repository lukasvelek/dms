<?php

namespace App\Components\Form;

use App\Components\Form\Elements\ElementDuo;
use App\Components\Form\Elements\Label;
use App\Components\Form\Elements\TextInput;
use App\UI\IUIRenderable;
use App\UI\TemplateEntity;

class FormFactory implements IUIRenderable {
    private array $elements;
    private array $handlerUrl;
    private string $method;

    public function __construct() {
        $this->elements = [];
        $this->handlerUrl = [];
        $this->method = 'POST';
    }

    /** ELEMENTS */
    public function addTextInput(string $name, ?string $label = null, mixed $value = null) {
        $e = new TextInput($name, $value);
        
        if($label !== null) {
            $l = new Label($name, $label);

            $e = new ElementDuo($e, $l);
        }

        $this->elements[$name] = $e;
    }
    /** ELEMENTS */

    public function render() {
        $template = $this->getTemplate();

        $template->form_action = $this->createUrl();
        $template->form_method = $this->method;

        $code = [];
        foreach($this->elements as $e) {
            $code[] = $e->render();
        }

        $template->form_content = implode('<br><br>', $code);

        return $template->render();
    }

    public function getElements() {
        return $this->elements;
    }

    private function createUrl() {
        $url = '?';

        $tmp = [];
        foreach($this->handlerUrl as $key => $value) {
            $tmp[$key] = $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }

    private function getTemplate() {
        $content = file_get_contents(__DIR__ . '\\formTemplate.html');

        return new TemplateEntity($content);
    }
}

?>