<?php

namespace DMS\Core;

use DMS\Helpers\ArrayStringHelper;

/**
 * Manager responsible for crypting
 * 
 * @author Lukas Velek
 */
class CryptManager {
    /**
     * Creates a password
     * 
     * @param bool $hash True if password must be hashed or false if not
     * @param int $length Password length
     * @return string Generated password
     */
    public static function createPassword(bool $hash = true, int $length = 8) {
        $cypher = CypherManager::createCypher($length);

        if($hash === TRUE) {
            return password_hash($cypher, PASSWORD_BCRYPT);
        } else {
            return $cypher;
        }
    }

    /**
     * Suggests a password
     * 
     * @param int $length Password length
     * @return string Generated password
     */
    public static function suggestPassword(int $length = 12) {
        $partCount = 3;
        
        if($length < 12 || ($length % $partCount) != 0) {
            return null;
        }

        $partLength = $length / $partCount;

        $parts = [];
        for($i = 0; $i < $partCount; $i++) {
            $parts[] = CypherManager::createCypher($partLength);

            if(($i + 1) < $partCount) {
                $parts[] .= '-';
            }
        }

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($parts);
    }

    /**
     * Hashes password
     * 
     * @param string $password Plain password text
     * @return string Hashed password
     */
    public static function hashPassword(string $password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

?>