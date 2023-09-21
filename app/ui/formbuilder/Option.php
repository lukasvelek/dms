<?php

namespace DMS\UI\FormBuilder;

class Option implements IBuildable {
  /**
   * @var string
   */
  private $value;

  /**
   * @var string
   */
  private $text;

  /**
   * @var string
   */
  private $selected;

  /**
   * @var string
   */
  public $script;

  public function __construct() {
    $this->value = '';
    $this->text = '';
    $this->selected = '';

    return $this;
  }

  public function setValue(string $value) {
    $this->value = $value;

    return $this;
  }

  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  public function select() {
    $this->selected = 'selected';

    return $this;
  }

  public function build() {
    $this->script = '<option value="' . $this->value . '" ' . $this->selected . '>' . $this->text . '</option>';

    return $this;
  }
}

?>
