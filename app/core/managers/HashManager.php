<?php

namespace App\Core\Managers;

class HashManager {
    private const SPECIAL_CHARACTERS = '_-.';
    private const CHARACTERS = 'abcdefghijklmnopqrstvuwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function createHash(int $length = 32, bool $useSpecialCharacters = false) {
        $hash = '';

        $characters = self::CHARACTERS;

        if($useSpecialCharacters) {
            $characters .= self::SPECIAL_CHARACTERS;
        }

        while(strlen($hash) <= $length) {
            $r = rand(0, strlen($characters));

            $hash .= $characters[$r];
        }

        return $hash;
    }

    public static function createExceptionHash() {
        return self::createHash(8);
    }
}

?>