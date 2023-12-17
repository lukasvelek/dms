<?php

namespace DMS\Core;

use DMS\Helpers\ArrayStringHelper;

class CryptManager {
    public static function createPassword(bool $hash = true, int $length = 8) {
        $cypher = CypherManager::createCypher($length);

        if($hash === TRUE) {
            return password_hash($cypher, PASSWORD_BCRYPT);
        } else {
            return $cypher;
        }
    }

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

    public static function hashPassword(string $password, string $salt) {
        return password_hash($password, PASSWORD_BCRYPT, array('salt' => $salt));
    }
}

?>