<?php

namespace DMS\UI\GridBuilder;

use DMS\UI\IBuildable;

class Grid implements IBuildable {
    private array $trs;
    private ?string $currentTr;

    public function __construct() {
        $this->trs = [];
        $this->currentTr = null;

        return $this;
    }

    public function tr(array $onRender = []) {
        if($this->currentTr == null) {
            $this->currentTr = '<tr';

            if(!empty($onRender) && array_key_exists('style', $onRender)) {
                $this->currentTr .= ' style="' . $onRender['style']() . '"';
            }

            $this->currentTr .= '>';
        } else {
            $this->currentTr .= '</tr>';
            $this->trs[] = $this->currentTr;
            $this->currentTr = '<tr>';
        }

        return $this;
    }
    
    public function endTr() {
        $this->currentTr .= '</tr>';
        $this->trs[] = $this->currentTr;
        $this->currentTr = null;

        return $this;
    }

    public function th(string $text, int $colspan = 1) {
        $this->currentTr .= '<th';

        if($colspan > 1) {
            $this->currentTr .= ' colspan="' . $colspan . '"';
        }

        $this->currentTr .=  '>' . $text . '</th>';

        return $this;
    }

    public function td(string $text, int $colspan = 1) {
        $this->currentTr .= '<td';

        if($colspan > 1) {
            $this->currentTr .= ' colspan="' . $colspan . '"';
        }

        $this->currentTr .= '>' . $text . '</td>';

        return $this;
    }

    public function build() {
        $code = '<table>';
        foreach($this->trs as $tr) {
            $code .= $tr;
        }
        $code .= '</table>';
        return $code;
    }
}

?>