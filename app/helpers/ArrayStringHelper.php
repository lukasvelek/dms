<?php

namespace DMS\Helpers;

class ArrayStringHelper {
    public static function createUnindexedStringFromUnindexedArray(array $data) {
        $string = '';

        foreach($data as $d) {
            $string .= $d;
        }

        return $string;
    }
}

?>