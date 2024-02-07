<?php

namespace DMS\Helpers;

class FormDataHelper {
    public static function post(string $key) {
        return htmlspecialchars($_POST[$key]);
    }

    public static function get(string $key) {
        return htmlspecialchars($_GET[$key]);
    }
}

?>