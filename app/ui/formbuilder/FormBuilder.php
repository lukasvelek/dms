<?php

namespace DMS\UI\FormBuilder;

use DMS\UI\IBuildable;

/**
 * FormBuilder allows to create HTML forms using PHP.
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class FormBuilder {
  private string $action;
  private string $method;
  private array $elements;
  private string $internalCode;
  private string $id;
  private string $encType;

  /**
   * The constructor that sets all class variables to empty values.
   */
  public function __construct() {
    $this->clean();
  }

  /**
   * Sets the encryption type
   * 
   * @param string $encType Encryption type
   * @return self
   */
  public function setEncType(string $encType = 'multipart/form-data') {
    $this->encType = $encType;

    return $this;
  }

  /**
   * Sets the form action
   * 
   * @param string $action Form action
   * @return self
   */
  public function setAction(string $action) {
    $this->action = $action;

    return $this;
  }

  /**
   * Sets the form method
   * 
   * @param string $method Form method
   * @return self
   */
  public function setMethod(string $method) {
    $this->method = $method;

    return $this;
  }

  /**
   * Sets the form ID
   * 
   * @param string $id Form ID
   * @return self
   */
  public function setId(string $id) {
    $this->id = $id;

    return $this;
  }

  public function addLabel(string $text, string $for) {
    $this->elements[] = (new Label())->setText($text)->setFor($for);
    
    return $this;
  }

  public function addText(string $name, string $value = '', string $placeholder = '') {
    $e = new Input();
    $e->setType('text')->setName($name);

    if($value != '') {
      $e->setValue($value);
    }
    if($placeholder != '') {
      $e->setPlaceHolder($placeholder);
    }

    $this->elements[] = $e;

    return $this;
  }

  public function addPassword(string $name, string $value = '', string $placeholder = '') {
    $e = new Input();
    $e->setType('password')->setName($name);

    if($value != '') {
      $e->setValue($value);
    }
    if($placeholder != '') {
      $e->setPlaceHolder($placeholder);
    }

    $this->elements[] = $e;

    return $this;
  }

  /**
   * Adds element to the form
   * 
   * @param IBuildable $element Form element
   * @return self
   */
  public function addElement(IBuildable $element) {
    if($element instanceof IBuildable) {
      $this->elements[] = $element;
    }

    return $this;
  }

  /**
   * Adds special code to the form
   * 
   * @param string $code Special code
   * @return Special
   */
  public function createSpecial(string $code) {
    return new Special($code);
  }

  /**
   * Creates a submit type input element
   * 
   * @param string $text Submit button text
   * @return Input Submit type input
   */
  public function createSubmit(string $text = 'Submit') {
    return $this->createInput()->setType('submit')->setValue($text);
  }

  public function createTextInput(string $name, string $value = '', string $placeholder = '') {
    $input = new Input();
    $input->setName($name);

    if($value != '') {
      $input->setValue($value);
    }

    if($placeholder != '') {
      $input->setPlaceHolder($placeholder);
    }

    return $input;
  }

  /**
   * Creates a input element
   * 
   * @return Input
   */
  public function createInput() {
    return new Input();
  }

  /**
   * Creates a label element
   * 
   * @return Label
   */
  public function createLabel() {
    return new Label();
  }

  /**
   * Creates a select element
   * 
   * @return Select
   */
  public function createSelect() {
    return new Select();
  }

  /**
   * Creates an option element for a select element
   * 
   * @param Option
   */
  public function createOption() {
    return new Option();
  }

  /**
   * Creates a text area element
   * 
   * @param TextArea
   */
  public function createTextArea() {
    return new TextArea();
  }

  /**
   * Adds a JS code
   * 
   * @param string $jsScript JS code
   * @return self
   */
  public function addJSScript(string $jsScript) {
    $this->internalCode .= $jsScript;

    return $this;
  }

  /**
   * Adds a loaded JS code
   * 
   * @param string $jsScript JS code
   * @return self
   */
  public function loadJSScript(string $jsScript) {
    $this->internalCode .= '<script type="text/javascript" src="' . $jsScript . '"></script>';

    return $this;
  }

  /**
   * Converts the FormBuilder class to HTML form code
   * 
   * @return string HTML code
   */
  public function build() {
    $code = [];

    $code[] = '<form action="' . $this->action . '" method="' . $this->method . '" id="' . $this->id . '"';

    if($this->encType != '') {
      $code[] = 'enctype="' . $this->encType . '"';
      $code[] = '>';
    } else {
      $code[] = '>';
    }

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

  /**
   * Sets all the class variables to empty values
   */
  private function clean() {
    $this->action = '';
    $this->method = '';
    $this->elements = array();
    $this->internalCode = '';
    $this->id = '';
    $this->encType = '';
  }
}

?>
