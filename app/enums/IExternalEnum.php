<?php

namespace DMS\Enums;

/**
 * External enum interface
 * 
 * @author Lukas Velek
 */
interface IExternalEnum {
    function getValues();
    function getValueByKey(string|int $key);
    function getKeyByValue(string|int $value);
    function getName();
    static function getEnum();
}

?>