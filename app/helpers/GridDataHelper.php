<?php

namespace DMS\Helpers;

/**
 * Grid data helper
 * 
 * @author Lukas Velek
 */
class GridDataHelper {
    /**
     * Renders colored text based on a boolean value
     * 
     * @param bool $value Value to be used
     * @param string $trueText Text printed out if the value is true
     * @param string $falseText Text printed out if the value is false
     * @param string $trueColor Color of text if the value is true
     * @param string $falseColor Color of text if the value is false
     * @return string HTML code
     */
    public static function renderBooleanValueWithColors(bool $value, string $trueText, string $falseText, string $trueColor = 'green', string $falseColor = 'red') {
        if($value === TRUE) {
            return '<span style="color: ' . $trueColor . '">' . $trueText . '</span>';
        } else {
            return '<span style="color: ' . $falseColor . '">' . $falseText . '</span>';
        }
    }
}

?>