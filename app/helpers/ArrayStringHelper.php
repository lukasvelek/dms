<?php

namespace DMS\Helpers;

/**
 * Array to string helper
 * 
 * @author Lukas Velek
 */
class ArrayStringHelper {
    /**
     * Convert array to string
     * 
     * @param array $data Array
     * @param null|string $delimeter Delimeter or null
     * @param bool $useSpaceAfterDelimeter True if space should be used after delimeter or false if not
     * @return string String
     */
    public static function createUnindexedStringFromUnindexedArray(array $data, ?string $delimeter = null, bool $useSpaceAfterDelimeter = true) {
        $string = '';

        $i = 0;
        foreach($data as $d) {
            if($delimeter != null) {
                if(($i + 1) == count($data)) {
                    $string .= $d;
                } else {
                    if($useSpaceAfterDelimeter) {
                        $string .= $d . $delimeter . ' ';
                    } else {
                        $string .= $d . $delimeter;
                    }
                }

                $i++;
            } else {
                $string .= $d;
            }
        }

        return $string;
    }
}

?>