<?php

namespace DMS\UI\TableBuilder;

class TableCol implements IBuildable {
  /**
   * @var string
   */
  private $text;

  /**
   * @var string
   */
  private $colspan;

  /**
   * @var string
   */
  private $bold;

  /**
   * @var string
   */
  private $textColor;

  /**
   * @var string
   */
  public $script;

  public function __construct() {
    $this->text = '';
    $this->colspan = '';
    $this->script = '';
    $this->textColor = 'black';
    $this->bold = 'td';

    return $this;
  }

  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  public function setColspan(string $colspan) {
    $this->colspan = 'colspan="' . $colspan . '"';

    return $this;
  }

  public function setBold() {
    $this->bold = 'th';

    return $this;
  }

  public function setTextColor(string $color) {
    $this->textColor = $color;

    return $this;
  }

  public function build() {
    $this->script = '<' . $this->bold . ' style="color: ' . $this->textColor . '" ' . $this->colspan . '>' . $this->text . '</' . $this->bold . '>';

    return $this;
  }
}

?>
