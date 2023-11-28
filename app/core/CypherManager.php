<?php

namespace DMS\Core;

class CypherManager {
    private const SYMBOLS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    public static function createCypher(int $length = 16) {
        $cypher = '';

        for($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen(self::SYMBOLS) - 1);

            $cypher .= self::SYMBOLS[$rand];
        }

        return $cypher;
    }
}

?>