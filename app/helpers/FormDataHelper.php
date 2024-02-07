<?php

namespace DMS\Helpers;

class FormDataHelper {
    public static function post(string $key, bool $escape = true) {
        if($escape === TRUE) {
            return htmlspecialchars($_POST[$key]);
        } else {
            return $_POST[$key];
        }
    }

    public static function get(string $key, bool $escape = true) {
        if($escape === TRUE) {
            return htmlspecialchars($_GET[$key]);
        } else {
            return $_GET[$key];
        }
    }

    public static function escape(string $text) {
        return htmlspecialchars($text);
    }
}

?>