<?php

namespace DMS\UI\FormBuilder;

class FormBuilder {
  /**
   * @var string
   */
  private $action;

  /**
   * @var string
   */
  private $method;

  /**
   * @var array
   */
  private $elements;

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

  public function build() {
    $code = array();

    $code[] = '<form action="' . $this->action . '" method="' . $this->method . '">';

    foreach($this->elements as $element) {
      $code[] = $element->build()->script;

      if($element instanceof Label) {
        $code[] = '<br>';
      } else if($element instanceof Input) {
        if($element->getType() != 'submit') {
          $code[] = '<br><br>';
        }
      } else if($element instanceof Select) {
        $code[] = '<br><br>';
      }
    }

    $code[] ='</form>';

    $singleLineCode = '';

    foreach($code as $c) {
      $singleLineCode .= $c;
    }

    $this->clean();

    return $singleLineCode;
  }

  public static function getTemporaryObject() {
    return new self();
  }

  private function clean() {
    $this->action = '';
    $this->method = '';
    $this->elements = array();
  }
}

?>
