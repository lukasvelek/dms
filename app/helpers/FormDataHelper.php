<?php

namespace DMS\Helpers;

/**
 * Form data helper
 * 
 * @author Lukas Velek
 */
class FormDataHelper {
    /**
     * Returns data by key from the global $_POST variable
     * 
     * @param string $key $_POST variable key
     * @param bool $escape True if the content should be escaped or false if not
     * @return string $_POST variable value
     */
    public static function post(string $key, bool $escape = true) {
        if($escape === TRUE) {
            return self::escape($_POST[$key]);
        } else {
            return $_POST[$key];
        }
    }

    /**
     * Returns data by key from the global $_GET variable
     * 
     * @param string $key $_GET variable key
     * @param bool $escape True if the content should be escaped or false if not
     * @return string $_GET variable value
     */
    public static function get(string $key, bool $escape = true) {
        if($escape === TRUE) {
            return self::escape($_GET[$key]);
        } else {
            return $_GET[$key];
        }
    }

    /**
     * Escapes given text
     * 
     * @param string $text Text
     * @return string Escaped text
     */
    public static function escape(string $text) {
        return htmlspecialchars($text);
    }
}

?>