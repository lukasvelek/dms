<?php

namespace DMS\UI\TableBuilder;

/**
 * TableCol class represents a table column
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class TableCol implements IBuildable {
  private string $text;
  private string $colspan;
  private string $bold;
  private string $textColor;
  private string $width;

  public string $script;

  /**
   * The TableCol constructor sets all the class variables to empty values
   * 
   * @return self
   */
  public function __construct() {
    $this->text = '';
    $this->colspan = '';
    $this->script = '';
    $this->textColor = 'black';
    $this->bold = 'td';
    $this->width = '';

    $this->script = '';

    return $this;
  }

  /**
   * Sets the column width
   * 
   * @param string $width Column width
   * @return self
   */
  public function setWidth(string $width) {
    $this->width = 'width: ' . $width . 'px';

    return $this;
  }

  /**
   * Sets the column text
   * 
   * @param string $text Column text
   * @return self
   */
  public function setText(string $text) {
    $this->text = $text;

    return $this;
  }

  /**
   * Sets the column span
   * 
   * @param string $colspan Column span
   * @return self
   */
  public function setColspan(string $colspan) {
    $this->colspan = 'colspan="' . $colspan . '"';

    return $this;
  }

  /**
   * Sets the column to header column
   * 
   * @return self
   */
  public function setBold() {
    $this->bold = 'th';

    return $this;
  }

  /**
   * Sets the column text color
   * 
   * @param string $color Column text color
   * @return self
   */
  public function setTextColor(string $color) {
    $this->textColor = $color;

    return $this;
  }

  /**
   * Converts the table column to HTML code
   * 
   * @return self
   */
  public function build() {
    $this->script = '<' . $this->bold . ' style="color: ' . $this->textColor . '; ' . $this->width . '" ' . $this->colspan . '>' . $this->text . '</' . $this->bold . '>';

    return $this;
  }
}

?>
