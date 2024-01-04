<?php

namespace DMS\Helpers;

class ArrayStringHelper {
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