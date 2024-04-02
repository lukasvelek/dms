<?php

namespace DMS\UI\FormBuilder;

use DMS\UI\IBuildable;

/**
 * Input form element allows the user to input data to the form.
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class Input implements IBuildable {
  private string $type;
  private string $name;
  private string $value;
  private string $hidden;
  private string $required;
  private string $disabled;
  private string $maxLength;
  private string $special;
  private string $min;
  private string $max;
  private string $placeHolder;
  private string $id;
  private string $step;
  private string $readonly;
  
  public string $script;

  /**
   * The input element constructor sets all the class variables to empty value.
   * 
   * @return self
   */
  public function __construct() {
    $this->type = '';
    $this->name = '';
    $this->value = '';
    $this->hidden = '';
    $this->required = '';
    $this->disabled = '';
    $this->maxLength = '';
    $this->special = '';
    $this->min = '';
    $this->max = '';
    $this->placeHolder = '';
    $this->id = '';
    $this->step = '';
    $this->readonly = '';

    $this->script = '';

    return $this;
  }

  /**
   * Returns the form type
   * 
   * @return string Form type
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the form type
   * 
   * @param string $type Form type
   * @return self
   */
  public function setType(string $type) {
    $this->type = 'type="' . $type . '"';

    return $this;
  }

  /**
   * Sets the form name and the form ID simulatenously
   * 
   * @param string $name Form name
   * @return self
   */
  public function setName(string $name) {
    $this->name = 'name="' . $name . '"';

    $this->setId($name);

    return $this;
  }

  /**
   * Sets the form value
   * 
   * @param string $value Form value
   * @return self
   */
  public function setValue(string $value) {
    $this->value = 'value="' . $value . '"';

    return $this;
  }

  /**
   * Sets the form element max length
   * 
   * @param string $maxLength Max length
   * @return self
   */
  public function setMaxLength(string $maxLength) {
    $this->maxLength = 'maxlength="' . $maxLength . '"';

    return $this;
  }

  /**
   * Sets the form element minimum
   * 
   * @param string $min Minimum
   * @return self
   */
  public function setMin(string $min) {
    $this->min = 'min="' . $min . '"';

    return $this;
  }

  /**
   * Sets the form element maximum
   * 
   * @param string $max Maximum
   * @return self
   */
  public function setMax(string $max) {
    $this->max = 'max="' . $max . '"';

    return $this;
  }

  /**
   * Hides the form element
   * 
   * @return self
   */
  public function hide() {
    $this->hidden = 'hidden';

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
   * Disables the form element
   * 
   * @return self
   */
  public function disable() {
    $this->disabled = 'disabled';

    return $this;
  }

  public function disableIfBoolTrue(bool $result) {
    if($result === TRUE) {
      $this->disabled = 'disabled';
    }

    return $this;
  }

  /**
   * Adds special setting to the form element
   * 
   * @param string $special Special setting
   * @return self
   */
  public function setSpecial(string $special) {
    $this->special = $special;

    return $this;
  }

  /**
   * Sets the form element placeholder
   * 
   * @param string $text Placeholder text
   * @return self
   */
  public function setPlaceHolder(string $text) {
    $this->placeHolder = 'placeholder="' . $text . '"';

    return $this;
  }

  /**
   * Sets the form element ID
   * 
   * @param string $id Form element ID
   * @return self
   */
  public function setId(string $id) {
    $this->id = 'id="' . $id . '"';

    return $this;
  }

  /**
   * Sets the form element step
   * 
   * @param string $step Form element step
   * @return self
   */
  public function setStep(string $step) {
    $this->step = 'step="' . $step . '"';

    return $this;
  }

  public function readonly() {
    $this->readonly = 'readonly';
    
    return $this;
  }

  public function readonlyIfBoolTrue(bool $result) {
    if($result === TRUE) {
      $this->readonly = 'readonly';
    }

    return $this;
  }

  /**
   * Converts the input class to HTML code
   * 
   * @return self
   */
  public function build() {
    $script = '<input '. $this->type . ' ' . $this->id . ' ' . $this->name . ' ' . $this->value . ' ' . $this->min . ' ' . $this->max . ' '
              . $this->maxLength . ' ' . $this->special . ' ' . $this->hidden . ' ' . $this->required . ' ' . $this->disabled . ' '
              . $this->placeHolder . ' ' . $this->step . ' ' . $this->readonly . ' >';

    $this->script = $script;

    return $this;
  }
}

?>
