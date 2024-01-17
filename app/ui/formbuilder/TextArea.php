<?php

namespace DMS\UI\FormBuilder;

/**
 * Text area form element allows users to input long texts in forms,
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class TextArea implements IBuildable {
  private string $name;
  private string $text;
  private string $required;
  private string $disabled;
  private string $readonly;

  public string $script;

  /**
   * The text area form element constructor sets all the class variables to empty values
   * 
   * @return self
   */
  public function __construct() {
    $this->name = '';
    $this->text = '';
    $this->required = '';
    $this->script = '';
    $this->disabled = '';
    $this->readonly = '';

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
   * Sets the form element as required
   * 
   * @return self
   */
  public function require() {
    $this->required = 'required';

    return $this;
  }

  /**
   * Sets the form element as disabled
   * 
   * @return self
   */
  public function disable() {
    $this->disabled = 'disabled';

    return $this;
  }

  public function readonly() {
    $this->readonly = 'readonly';

    return $this;
  }

  /**
   * Converts the text area class to HTML code
   * 
   * @return self
   */
  public function build() {
    $this->script = '<textarea name="' . $this->name . '" ' . $this->required . ' ' . $this->disabled . ' ' . $this->readonly . '>' . $this->text . '</textarea>';

    return $this;
  }
}

?>
