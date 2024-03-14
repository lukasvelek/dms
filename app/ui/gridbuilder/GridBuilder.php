<?php

namespace DMS\UI;

class GridBuilder {
    private array $actions;
    private array $columns;
    private array $dataSourceArray;
    private array $callbacks;

    private int $tableBorder;
    
    private ?string $headerCheckbox;
    private string $emptyDataSourceMessage;

    private mixed $renderRowCheckbox;
    private mixed $dataSourceCallback;

    private bool $reverse;
    private bool $alwaysDrawHeaderCheckbox;

    public function __construct() {
        $this->columns = [];
        $this->actions = [];
        $this->dataSourceArray = [];
        $this->callbacks = [];

        $this->tableBorder = 1;

        $this->headerCheckbox = null;
        $this->renderRowCheckbox = null;
        $this->dataSourceCallback = null;
        $this->emptyDataSourceMessage = 'No data found';
        $this->reverse = false;
        $this->alwaysDrawHeaderCheckbox = false;
    }

    public function reverseData() {
        $this->reverse = true;
    }

    public function addHeaderCheckbox(string $id, string $onChange, bool $drawAlways = false) {
        $this->headerCheckbox = '<input type="checkbox" id="' . $id . '" onchange="' . $onChange . '">';
        $this->alwaysDrawHeaderCheckbox = $drawAlways;
    }

    public function addRowCheckbox(callable $renderRowCheckbox) {
        $this->renderRowCheckbox = $renderRowCheckbox;
    }

    public function addOnColumnRender(string $entityVarName, callable $func) {
        $this->callbacks[$entityVarName] = $func;
    }

    public function addColumn(string $entityVarName, string $title) {
        $this->columns[$entityVarName] = $title;
    }

    public function addColumns(array $columns) {
        foreach($columns as $k => $v) {
            $this->columns[$k] = $v;
        }
    }

    public function addAction(callable $createUrl) {
        $this->actions[] = $createUrl;
    }

    public function addDataSource(array $objectArray) {
        $this->dataSourceArray = $objectArray;
    }

    public function addDataSourceCallback(callable $dataSourceCallback) {
        $this->dataSourceCallback = $dataSourceCallback;
    }

    public function addEmptyDataSourceMessage(string $text) {
        $this->emptyDataSourceMessage = $text;
    }

    public function setTableBorder(int $border) {
        $this->tableBorder = $border;
    }

    public function build() {
        $code = '<table border="' . $this->tableBorder . '" id="tablebuilder-table">';

        // title
        $headerRow = '<tr>';
        if(!is_null($this->headerCheckbox) && (is_callable($this->renderRowCheckbox) || $this->alwaysDrawHeaderCheckbox)) {
            $headerRow .= '<th>' . $this->headerCheckbox . '</th>';
        }
        if(!empty($this->actions)) {
            $headerRow .= '<th';

            if(count($this->actions) > 1) {
                $headerRow .= ' colspan="' . count($this->actions) . '"';
            }

            $headerRow .= '>';

            $headerRow .= 'Actions</th>';
        }
        foreach($this->columns as $varName => $title) {
            $headerRow .= '<th>' . $title . '</th>';
        }
        $headerRow .= '</tr>';
        $code .= $headerRow;
        // end of title

        // data
        $entityRows = [];
        if(empty($this->dataSourceArray) && (is_null($this->dataSourceCallback) || !is_callable($this->dataSourceCallback))) {
            $entityRow = '<tr><td';

            $colspan = count($this->actions) + count($this->columns);

            if(!is_null($this->headerCheckbox)) {
                $colspan += 1;
            }

            $entityRow .= ' colspan="' . $colspan . '" id="grid-empty-message">' . $this->emptyDataSourceMessage . '</td></tr>';
            $entityRows[] = $entityRow;
        } else {
            if(empty($this->dataSourceArray)) {
                $this->dataSourceArray = call_user_func($this->dataSourceCallback);
            }

            if(empty($this->dataSourceArray) || is_null($this->dataSourceArray)) {
                $entityRow = '<tr><td';

                $colspan = count($this->actions) + count($this->columns);

                if(!is_null($this->headerCheckbox)) {
                    $colspan += 1;
                }

                $entityRow .= ' colspan="' . $colspan . '" id="grid-empty-message">' . $this->emptyDataSourceMessage . '</td></tr>';
                $entityRows[] = $entityRow;
            } else {
                foreach($this->dataSourceArray as $entity) {
                    $entityRow = '<tr>';
    
                    if(!is_null($this->renderRowCheckbox)) {
                        $entityRow .= '<td>' . call_user_func($this->renderRowCheckbox, $entity) . '</td>';
                    }
        
                    foreach($this->actions as $action) {
                        $entityRow .= '<td>' . $action($entity) . '</td>';
                    }
        
                    foreach($this->columns as $varName => $title) {
                        $objectVarName = ucfirst($varName);
        
                        if(method_exists($entity, 'get' . $objectVarName)) {
                            if(array_key_exists($varName, $this->callbacks)) {
                                $entityRow .= '<td>' . $this->callbacks[$varName]($entity) . '</td>';
                            } else {
                                $entityRow .= '<td>' . ($entity->{'get' . $objectVarName}() ?? '-') . '</td>';
                            }
                        } else {
                            if(array_key_exists($varName, $this->callbacks)) {
                                $entityRow .= '<td>' . $this->callbacks[$varName]($entity) . '</td>';
                            } else {
                                $entityRow .= '<td style="background-color: red">' . $varName . '</td>';
                            }
                        }
                    }
        
                    $entityRow .= '</tr>';
                    $entityRows[] = $entityRow;
                }
            }
        }

        if($this->reverse === TRUE) {
            $tmp = [];

            for($i = (count($entityRows) - 1); $i >= 0; $i--) {
                $tmp[] = $entityRows[$i];
            }

            $entityRows = $tmp;
        }

        foreach($entityRows as $entityRow) {
            $code .= $entityRow;
        }
        // end of data

        $code .= '</table>';

        return $code;
    }

    public static function createEmptyGrid(array $columns, bool $addHeaderCheckbox = false, string $headerCheckboxId = '', string $headerCheckboxAction = '') {
        $gb = new self();
        $gb->addColumns($columns);
        $gb->addDataSource([]);
        if($addHeaderCheckbox === TRUE) {
            $gb->addHeaderCheckbox($headerCheckboxId, $headerCheckboxAction, true);
        }
        return $gb->build();
    }
}

?>