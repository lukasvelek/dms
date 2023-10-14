<?php

namespace DMS\Core;

class ScriptLoader {
    /**
     * Method returns HTML code to have JS script loaded.
     * 
     * @param string $path Path to the JS script
     * @return string HTML code
     */
    public static function loadJSScript(string $path) {
        $code = '<script type="text/javascript" src="' . $path . '"></script>';

        return $code;
    }
}

?>