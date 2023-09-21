<?php

namespace DMS\UI\TableBuilder;

class TableBuilder {
  /**
   * @var string
   */
  private $border;

  /**
   * @var array
   */
  private $rows;

  public function __construct() {
    $this->clean();
  }

  public function setBorder(string $border) {
    $this->border = $border;

    return $this;
  }

  public function setRows(array $rows) {
    foreach($rows as $row) {
      $this->addRow($row);
    }

    return $this;
  }

  public function addRow(TableRow $row) {
    if($row instanceof TableRow) {
      $this->rows[] = $row;
    }

    return $this;
  }

  public function createRow() {
    return new TableRow();
  }

  public function createCol() {
    return new TableCol();
  }

  public function build() {
    $code = array();

    $code[] = '<table border="' . $this->border . '">';

    if(!empty($this->rows)) {
      foreach($this->rows as $row) {
        $code[] = $row->build()->script;
      }
    }

    $code[] = '</table>';

    $singleLineCode = '';

    foreach($code as $c) {
      $singleLineCode .= $c;
    }

    $this->clean();

    return $singleLineCode;
  }

  private function clean() {
    $this->border = '';
  }
}

?>
