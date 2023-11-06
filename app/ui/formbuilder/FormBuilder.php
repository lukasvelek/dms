<?php

namespace DMS\UI\FormBuilder;

class FormBuilder {
  private string $action;
  private string $method;
  private array $elements;
  private string $internalCode;
  private string $id;

  public function __construct() {
    $this->clean();
  }

  public function setAction(string $action) {
    $this->action = $action;

    return $this;
  }

  public function setMethod(string $method) {
    $this->method = $method;

    return $this;
  }

  public function setId(string $id) {
    $this->id = $id;

    return $this;
  }

  public function addElement(IBuildable $element) {
    if($element instanceof IBuildable) {
      $this->elements[] = $element;
    }

    return $this;
  }

  public function createSpecial(string $code) {
    return new Special($code);
  }

  public function createSubmit(string $text = 'Submit') {
    $input = new Input();

    return $input->setType('submit')->setValue($text);
  }

  public function createInput() {
    return new Input();
  }

  public function createLabel() {
    return new Label();
  }

  public function createSelect() {
    return new Select();
  }

  public function createOption() {
    return new Option();
  }

  public function createTextArea() {
    return new TextArea();
  }

  public function addJSScript(string $jsScript) {
    $this->internalCode .= $jsScript;

    return $this;
  }

  public function loadJSScript(string $jsScript) {
    $this->internalCode .= '<script type="text/javascript" src="' . $jsScript . '"></script>';

    return $this;
  }

  public function build() {
    $code = [];

    $code[] = '<form action="' . $this->action . '" method="' . $this->method . '" id="' . $this->id . '">';

    foreach($this->elements as $element) {
      $code[] = $element->build()->script;

      if($element instanceof Label) {
        $code[] = '<br>';
      } else if($element instanceof Input) {
        if($element->getType() != 'submit') {
          $code[] = '<br><br>';
        }
      } else if($element instanceof Select || $element instanceof TextArea) {
        $code[] = '<br><br>';
      }
    }

    $code[] ='</form>';

    $code[] = $this->internalCode;

    $singleLineCode = '';

    foreach($code as $c) {
      $singleLineCode .= $c;
    }

    $this->clean();

    return $singleLineCode;
  }

  /**
   * Method returns a temporary object with NULL parameters.
   * 
   * @return FormBuilder
   */
  public static function getTemporaryObject() {
    return new self();
  }

  private function clean() {
    $this->action = '';
    $this->method = '';
    $this->elements = array();
    $this->internalCode = '';
    $this->id = '';
  }
}

?>
