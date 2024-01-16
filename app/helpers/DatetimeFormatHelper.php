<?php

namespace DMS\Helpers;

use DMS\Core\AppConfiguration;
use DMS\Entities\User;

class DatetimeFormatHelper {
    public static function formatDateByUserDefaultFormat(string $datetime, User $user) {
        $format = AppConfiguration::getDefaultDatetimeFormat();

        if($user->getDefaultUserDateTimeFormat() !== NULL) {
            $format = $user->getDefaultUserDateTimeFormat();
        }

        return date($format, strtotime($datetime));
    }

    public static function formatDateByFormat(string $datetime, string $format) {
        return date($format, strtotime($datetime));
    }
}

?>