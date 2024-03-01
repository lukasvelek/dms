<?php

namespace DMS\UI\TableBuilder;

use DMS\UI\IBuildable;

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
  private string $id;
  private string $noData;

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
    $this->id = '';
    $this->noData = '';

    return $this;
  }

  public function setNoData() {
    $this->noData = ' class="info" ';

    return $this;
  }

  public function setId(string $id) {
    $this->id = 'id="' . $id . '"';

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

    if($this->text == 'No data found') {
      $this->noData = ' class="info" ';
    }

    return $this;
  }

  /**
   * Adds text to the column text
   * 
   * @param string $text Column text
   * @return self
   */
  public function addText(string $text) {
    $this->text .= $text;

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
    $this->script = '<' . $this->bold . ' ' . $this->noData . ' ' .  'style="color: ' . $this->textColor . '; ' . $this->width . '" ' . $this->colspan . ' ' . $this->id . '>' . $this->text . '</' . $this->bold . '>';

    return $this;
  }
}

?>
