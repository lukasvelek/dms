<?php

namespace DMS\UI\TableBuilder;

class TableRow implements IBuildable {
  /**
   * @var array
   */
  private $cols;

  /**
   * @var string
   */
  private $colspan;

  public $script;

  public function __construct() {
    $this->cols = array();
    $this->colspan = '';

    return $this;
  }

  public function setColspan(string $colspan) {
    $this->colspan = 'colspan="' . $colspan . '"';

    return $this;
  }

  public function setCols(array $cols) {
    foreach($cols as $col) {
      $this->addCol($col);
    }

    return $this;
  }

  public function addCol(TableCol $col) {
    if($col instanceof TableCol) {
      $this->cols[] = $col;
    }

    return $this;
  }

  public function createCol() {
    return new TableCol();
  }

  public function build() {
    $code[] = '<tr ' . $this->colspan . '>';

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
