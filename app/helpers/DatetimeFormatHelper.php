<?php

namespace DMS\Helpers;

use DMS\Constnats\DatetimeFormats;
use DMS\Core\AppConfiguration;
use DMS\Entities\User;

class DatetimeFormatHelper {
    public static function formatDateByUserDefaultFormat(string $datetime, User $user) {
        if($datetime == '-' || $datetime == '' || $datetime === NULL) {
            return $datetime;
        }

        $format = AppConfiguration::getDefaultDatetimeFormat();

        if($user->getDefaultUserDateTimeFormat() !== NULL) {
            $format = $user->getDefaultUserDateTimeFormat();
        }

        return date($format, strtotime($datetime));
    }

    public static function formatDateByFormat(string $datetime, string $format) {
        if($datetime == '-' || $datetime == '' || $datetime === NULL) {
            return $datetime;
        }

        if(!in_array($format, DatetimeFormats::$formats)) {
            return $datetime;
        }

        return date($format, strtotime($datetime));
    }
}

?>