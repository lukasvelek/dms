<?php

namespace DMS\UI\FormBuilder;

class TextArea implements IBuildable {
  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $text;

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
  public $script;

  public function __construct() {
    $this->name = '';
    $this->text = '';
    $this->required = '';

    $this->script = '';
  }

  public function setName(string $name) {
    $this->name = $name;

    return $this;
  }

  public function setText(string $text) {
    $this->text = $text;

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

  public function build() {
    $this->script = '<textarea name="' . $this->name . '" ' . $this->required . ' ' . $this->disabled . '>' . $this->text . '</textarea>';

    return $this;
  }
}

?>
