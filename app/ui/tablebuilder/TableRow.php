<?php

namespace DMS\UI\TableBuilder;

/**
 * TableROw class represents a table row
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class TableRow implements IBuildable {
  private array $cols;
  private string $colspan;
  private string $class;

  public string $script;

  /**
   * The TableRow constructor sets all the class variables to empty values
   * 
   * @return self
   */
  public function __construct() {
    $this->cols = array();
    $this->colspan = '';
    $this->class = '';
    $this->script = '';

    return $this;
  }

  /**
   * Sets the row style class
   * 
   * @param string $class Row style class
   * @return self
   */
  public function setClass(string $class) {
    $this->class = 'class="' . $class . '"';

    return $this;
  }

  /**
   * Sets the row span
   * 
   * @param string $colspan Row span
   * @return self
   */
  public function setColspan(string $colspan) {
    $this->colspan = 'colspan="' . $colspan . '"';

    return $this;
  }

  /**
   * Adds the table columns
   * 
   * @param array $cols Table columns
   * @return self
   */
  public function setCols(array $cols) {
    foreach($cols as $col) {
      $this->addCol($col);
    }

    return $this;
  }

  /**
   * Adds a table column
   * 
   * @param TableCol $col Table column
   * @return self
   */
  public function addCol(TableCol $col) {
    if($col instanceof TableCol) {
      $this->cols[] = $col;
    }

    return $this;
  }

  /**
   * Creates a table column instance
   * 
   * @return TableCol
   */
  public function createCol() {
    return new TableCol();
  }

  public function build() {
    $code[] = '<tr ' . $this->colspan . ' ' . $this->class . '>';

    foreach($this->cols as $col) {
      $code[] = $col->build()->script;
    }

    $code[] = '</tr>';

    foreach($code as $c) {
      $this->script .= $c;
    }

    return $this;
  }
}

?>
