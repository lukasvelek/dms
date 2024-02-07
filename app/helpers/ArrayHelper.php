<?php

namespace DMS\Helpers;

class ArrayHelper {
    public static function deleteKeysFromArray(array &$array, array $keys) {
        foreach($keys as $key) {
            if(array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
    }

    public static function formatArrayData(array &$array) {
        $temp = [];
        foreach($array as $key => $value) {
            $temp[$key] = FormDataHelper::escape($value);
        }
        $array = $temp;
        return $array;
    }
}

?>