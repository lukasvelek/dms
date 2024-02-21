<?php

namespace DMS\Core;

/**
 * Manager responsible for cyphers and hashes
 * 
 * @author Lukas Velek
 */
class CypherManager {
    private const SYMBOLS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    /**
     * Generates a cypher
     * 
     * @param int $length Cypher length
     * @return string Generated cypher
     */
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