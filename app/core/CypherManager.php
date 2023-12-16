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

    public static function createCypherSkipSymbols(array $symbolsToSkip, int $length = 16) {
        $cypher = '';

        for($i = 0; $i < $length; $i++) {
            $skip = true;

            while($skip == true) {
                $rand = rand(0, strlen(self::SYMBOLS) - 1);

                $symbol = self::SYMBOLS[$rand];

                if(!in_array($symbol, $symbolsToSkip)) {
                    $skip = false;

                    $cypher .= $symbol;
                }
            }
        }

        return $cypher;
    }
}

?>