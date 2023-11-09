<?php

namespace DMS\UI\TableBuilder;

class TableRow implements IBuildable {
  private array $cols;
  private string $colspan;
  private string $class;

  public $script;

  public function __construct() {
    $this->cols = array();
    $this->colspan = '';
    $this->class = '';

    return $this;
  }

  public function setClass(string $class) {
    $this->class = 'class="' . $class . '"';

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
