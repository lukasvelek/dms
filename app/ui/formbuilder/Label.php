<?php

namespace DMS\UI\FormBuilder;

use DMS\UI\IBuildable;

/**
 * Label form element displays text to the user
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class Label implements IBuildable {
  private string $for;
  private string $text;
  private string $id;
  private bool $required;

  public string $script;

  /**
   * The label form element constructor sets all the class variables to empty value
   * 
   * @return self
   */
  public function __construct() {
    $this->for = '';
    $this->text = '';
    $this->id = '';
    $this->required = false;

    $this->script = '';

    return $this;
  }

  /**
   * Sets the form element for
   * 
   * @param string $for For
   * @return self
   */
  public function setFor(string $for) {
    $this->for = $for;

    return $this;
  }

  /**
   * Sets the form element text
   * 
   * @param string $text Text
   * @return self
   */
  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  /**
   * Sets the form element ID
   * 
   * @param string $id ID
   * @return self
   */
  public function setId(string $id) {
    $this->id = 'id="' . $id . '"';

    return $this;
  }

  /**
   * Sets the label as a label for a required element
   * 
   * @param bool $required True if the element is required or false if not
   * @return self
   */
  public function setRequired(bool $required = true) {
    $this->required = $required;
    
    return $this;
  }

  /**
   * Converts the label class to HTML code
   * 
   * @return self
   */
  public function build() {
    $script = '<label ' . $this->id . ' for="' . $this->for . '">' . $this->text;

    if($this->text[strlen($this->text) - 1] != ':') {
      $script .= ':';
    }

    if($this->required === TRUE) {
      $script .= '<span style="color: red"> *</span>';
    }

    $script .= '</label>';

    $this->script = $script;

    return $this;
  }
}

?>
