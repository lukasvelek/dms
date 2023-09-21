<?php

namespace DMS\UI\FormBuilder;

class Input implements IBuildable {
  /**
   * @var string
   */
  private $type;

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $value;

  /**
   * @var string
   */
  private $hidden;

  /**
   * @var string
   */
  private $required;

  /**
   * @var string
   */
  private $disabled;

  /**
   * @var string
   */
  private $maxLength;

  /**
   * @var string
   */
  private $special;

  /**
   * @var string
   */
  private $min;

  /**
   * @var string
   */
  private $max;

  /**
   * @var string
   */
  private $placeHolder;

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $step;

  /**
   * @var string
   */
  public $script;

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

    $this->script = '';

    return $this;
  }

  public function getType() {
    return $this->type;
  }

  public function setType(string $type) {
    $this->type = 'type="' . $type . '"';

    return $this;
  }

  public function setName(string $name) {
    $this->name = 'name="' . $name . '"';

    return $this;
  }

  public function setValue(string $value) {
    $this->value = 'value="' . $value . '"';

    return $this;
  }

  public function setMaxLength(string $maxLength) {
    $this->maxLength = 'maxlength="' . $maxLength . '"';

    return $this;
  }

  public function setMin(string $min) {
    $this->min = 'min="' . $min . '"';

    return $this;
  }

  public function setMax(string $max) {
    $this->max = 'max="' . $max . '"';

    return $this;
  }

  public function hide() {
    $this->hidden = 'hidden';

    return $this;
  }

  public function require() {
    $this->required = 'required';

    return $this;
  }

  public function disable() {
    $this->disabled = 'disabled';

    return $this;
  }

  public function setSpecial(string $special) {
    $this->special = $special;

    return $this;
  }

  public function setPlaceHolder(string $text) {
    $this->placeHolder = 'placeholder="' . $text . '"';

    return $this;
  }

  public function setId(string $text) {
    $this->id = 'id="' . $text . '"';

    return $this;
  }

  public function setStep(string $step) {
    $this->step = 'step="' . $step . '"';

    return $this;
  }

  public function build() {
    $script = '<input '. $this->type . ' ' . $this->id . ' ' . $this->name . ' ' . $this->value . ' ' . $this->min . ' ' . $this->max . ' '
              . $this->maxLength . ' ' . $this->special . ' ' . $this->hidden . ' ' . $this->required . ' ' . $this->disabled . ' '
              . $this->placeHolder . ' ' . $this->step . ' >';

    $this->script = $script;

    return $this;
  }
}

?>
