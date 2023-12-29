<?php

namespace DMS\UI\FormBuilder;

/**
 * Option form element is used in the select form element
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class Option implements IBuildable {
  private string $value;
  private string $text;
  private string $selected;

  public string $script;

  /**
   * The option form element constructor sets all the class variables to empty values
   * 
   * @return self
   */
  public function __construct() {
    $this->value = '';
    $this->text = '';
    $this->selected = '';

    $this->script = '';

    return $this;
  }

  /**
   * Sets the form element value
   * 
   * @param string $value Value
   * @return self
   */
  public function setValue(string $value) {
    $this->value = $value;

    return $this;
  }

  /**
   * Sets the form element text
   * 
   * @param string $text Test
   * @return self
   */
  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  /**
   * Sets the form element as selected
   * 
   * @return self
   */
  public function select() {
    $this->selected = 'selected';

    return $this;
  }

  /**
   * Converts the option class to HTML code
   * 
   * @return self
   */
  public function build() {
    $this->script = '<option value="' . $this->value . '" ' . $this->selected . '>' . $this->text . '</option>';

    return $this;
  }
}

?>
