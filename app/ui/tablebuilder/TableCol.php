<?php

namespace DMS\UI\TableBuilder;

class TableCol implements IBuildable {
  private string $text;
  private string $colspan;
  private string $bold;
  private string $textColor;
  private string $width;

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
    $this->width = '';

    return $this;
  }

  public function setWidth(string $width) {
    $this->width = 'width: ' . $width . 'px';

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
    $this->script = '<' . $this->bold . ' style="color: ' . $this->textColor . '; ' . $this->width . '" ' . $this->colspan . '>' . $this->text . '</' . $this->bold . '>';

    return $this;
  }
}

?>
