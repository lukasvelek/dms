<?php

namespace DMS\Core;

class CryptManager {
    public static function createPassword(bool $hash = true, int $length = 8) {
        $cypher = CypherManager::createCypher($length);

        if($hash === TRUE) {
            return password_hash($cypher, PASSWORD_BCRYPT);
        } else {
            return $cypher;
        }
    }

    public static function createAdvPassword(string $salt, bool $hash = true, int $length = 12) {
        $saltLetters = [];

        for($i = 0; $i < strlen($salt); $i++) {
            $saltLetters[] = $salt[$i];
        }

        $cypher = CypherManager::createCypherSkipSymbols($saltLetters, $length);

        if($hash === TRUE) {
            return password_hash($cypher, PASSWORD_BCRYPT);
        } else {
            return $cypher;
        }
    }

    public static function hashPassword(string $password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

?>