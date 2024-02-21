<?php

namespace DMS\Helpers;

class GridDataHelper {
    public static function renderBooleanValueWithColors(bool $value, string $trueText, string $falseText, string $trueColor = 'green', string $falseColor = 'red') {
        if($value === TRUE) {
            return '<span style="color: ' . $trueColor . '">' . $trueText . '</span>';
        } else {
            return '<span style="color: ' . $falseColor . '">' . $falseText . '</span>';
        }
    }
}

?>