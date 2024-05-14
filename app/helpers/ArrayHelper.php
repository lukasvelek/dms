<?php

namespace DMS\Helpers;

/**
 * Array helper
 * 
 * @author Lukas Velek
 */
class ArrayHelper {
    /**
     * Delete defined keys from an array
     * 
     * @param array $array Array
     * @param array $keys Array keys to be deleted
     */
    public static function deleteKeysFromArray(array &$array, array $keys) {
        foreach($keys as $key) {
            if(array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
    }

    /**
     * Format array data
     * 
     * @param array $array Array
     * @return array Formatted array
     */
    public static function formatArrayData(array &$array) {
        $temp = [];
        foreach($array as $key => $value) {
            $temp[$key] = FormDataHelper::escape($value);
        }
        $array = $temp;
        return $array;
    }

    public static function escapeArrayData(array &$array) {
        $temp = [];
        foreach($array as $key => $value) {
            $temp[$key] = str_replace('\\', '\\\\', $value);
        }

        $array = $temp;
        return $array;
    }
}

?>