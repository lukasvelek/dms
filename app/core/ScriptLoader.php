<?php

namespace DMS\Core;

/**
 * Allows using external scripts
 * 
 * @author Lukas Velek
 */
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

    /**
     * This method displays a JS confirm popup window and based on user's decision it redirects
     * 
     * @param string $text Text to display in the popup window
     * @param array $urlConfirm Confirm URL
     * @param array $urlClose Close URL
     */
    public static function confirmUser(string $text, array $urlConfirm, array $urlClose) {
        $urlCo = '?';
        $urlCl = '?';

        $i = 0;
        foreach($urlConfirm as $k => $v) {
            if(($i + 1) == count($urlConfirm)) {
                $urlCo .= $k . '=' . $v;
            } else {
                $urlCo .= $k . '=' . $v . '&';
            }

            $i++;
        }

        $i = 0;
        foreach($urlClose as $k => $v) {
            if(($i + 1) == count($urlClose)) {
                $urlCl .= $k . '=' . $v;
            } else {
                $urlCl .= $k . '=' . $v . '&';
            }

            $i++;
        }


        $code = '
            <script type="text/javascript">
                let result = confirm("' . $text . '");

                if(result == true) {
                    location.replace("' . $urlCo . '");
                } else {
                    location.replace("' . $urlCl . '");
                }
            </script>
        ';

        return $code;
    }

    /**
     * Alerts a user with given text
     * 
     * @param string $text Alert text
     * @param array $urlConfirm URL used to redirect the user
     * @return string $code HTML code
     */
    public static function alert(string $text, array $urlConfirm) {
        $url = '';

        $i = 0;
        foreach($urlConfirm as $k => $v) {
            if(($i + 1) == count($urlConfirm)) {
                $url .= $k . '=' . $v;
            } else {
                $url .= $k . '=' . $v . '&';
            }

            $i++;
        }

        $code = '
            <script type="text/javascript">
                alert("' . $text . '");
                location.replace("' . $url . '");
            </script>
        ';

        return $code;
    }
}

?>