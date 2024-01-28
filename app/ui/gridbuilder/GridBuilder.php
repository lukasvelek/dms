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
    }

    public function addHeaderCheckbox(string $id, string $onChange) {
        $this->headerCheckbox = '<input type="checkbox" id="' . $id . '" onchange="' . $onChange . '">';
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
        $code = '<table';

        if($this->tableBorder > 1) {
            $code .= ' border="' . $this->tableBorder . '"';
        }

        $code .= '>';

        // title
        $headerRow = '<tr>';
        if(!is_null($this->headerCheckbox) && is_callable($this->renderRowCheckbox)) {
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
        $code .= $headerRow;
        // end of title

        // data
        if(empty($this->dataSourceArray) && (is_null($this->dataSourceCallback) || !is_callable($this->dataSourceCallback))) {
            $entityRow = '<tr><td';

            $colspan = count($this->actions) + count($this->columns);

            if(!is_null($this->headerCheckbox)) {
                $colspan += 1;
            }

            $entityRow .= ' colspan="' . $colspan . '">' . $this->emptyDataSourceMessage . '</td></tr>';
            $code .= $entityRow;
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

                $entityRow .= ' colspan="' . $colspan . '">' . $this->emptyDataSourceMessage . '</td></tr>';
                $code .= $entityRow;
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
                    $code .= $entityRow;
                }
            }
        }
        // end of data

        $code .= '</table>';

        return $code;
    }
}

?>