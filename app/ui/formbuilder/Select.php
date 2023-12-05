<?php

namespace DMS\UI\FormBuilder;

/**
 * Select form element is used to allow users to select values for form
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class Select implements IBuildable {
  private string $name;
  private array $options;
  private string $disable;
  private string $id;

  public string $script;

  /**
   * The Select form element constructor sets all the class variables to empty values
   * 
   * @return self
   */
  public function __construct() {
    $this->name = '';
    $this->options = array();
    $this->script = '';
    $this->disable = '';
    $this->id = '';

    return $this;
  }

  /**
   * Sets the form element name
   * 
   * @param string $name Name
   * @return self
   */
  public function setName(string $name) {
    $this->name = $name;

    return $this;
  }

  /**
   * Sets the form element ID
   * 
   * @param string $id ID
   * @return self
   */
  public function setId(string $id) {
    $this->id = $id;

    return $this;
  }

  /**
   * Adds options to the select form element
   * 
   * @param array $options Option element array
   * @return self
   */
  public function addOptions(array $options) {
    foreach($options as $o) {
      $this->options[] = $o;
    }

    return $this;
  }

  /**
   * Creates options based on the passed array of data and adds them to the select form element.
   * The data must have this form-factor:
   * $array = [
   *  [
   *    'value' => 'VALUE THAT IS PASSED AFTER FORM SUBMIT',
   *    'text' => 'TEXT THAT IS DISPLAYED IN THE FORM'
   *  ]
   * ];
   * 
   * Also if the option element is supposed to be selected it must contain 'select' element like so:
   * $array = [
   *  [
   *    'value' => 'VALUE THAT IS PASSED AFTER FORM SUBMIT',
   *    'text' => 'TEXT THAT IS DISPLAYED IN THE FORM',
   *    'select' => 'selected'
   *  ]
   * ];
   * 
   * @param array $array Data array
   * @return self
   */
  public function addOptionsBasedOnArray(array $array) {
    $options = array();

    foreach($array as $a) {
      $value = $a['value'];
      $text = $a['text'];

      if(isset($a['selected'])) {
        $selected = $a['selected'];
      } else {
        $selected = '';
      }

      $option = new Option();
      $option = $option->setValue($value)
                       ->setText($text);

      if($selected != '') {
        $option = $option->select();
      }

      $options[] = $option;
    }

    $this->addOptions($options);

    return $this;
  }

  public function disable() {
    $this->disable = 'disabled';

    return $this;
  }

  /**
   * Converts the select class to HTML code
   * 
   * @return self
   */
  public function build() {
    $script = '<select name="' . $this->name . '" ' . $this->disable . ' id="' . $this->id . '">';

    foreach($this->options as $o) {
      $script .= $o->build()->script;
    }

    $script .= '</select>';

    $this->script = $script;

    return $this;
  }
}

?>
