<?php

namespace DMS\UI\FormBuilder;

class Label implements IBuildable {
  /**
   * @var string
   */
  private $for;

  /**
   * @var string
   */
  private $text;

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  public $script;

  public function __construct() {
    $this->for = '';
    $this->text = '';
    $this->id = '';

    return $this;
  }

  public function setFor(string $for) {
    $this->for = $for;

    return $this;
  }

  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  public function setId(string $text) {
    $this->id = 'id="' . $text . '"';

    return $this;
  }

  public function build() {
    $script = '<label ' . $this->id . ' for="' . $this->for . '">' . $this->text . '</label>';

    $this->script = $script;

    return $this;
  }
}

?>
