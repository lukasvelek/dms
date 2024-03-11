<?php

namespace DMS\Helpers;

use DMS\Constants\DatetimeFormats;
use DMS\Core\AppConfiguration;
use DMS\Core\Datetime;
use DMS\Entities\User;

/**
 * Datetime format helper
 * 
 * @author Lukas Velek
 */
class DatetimeFormatHelper {
    /**
     * Formats datetime by user's default format
     * 
     * @param string $datetime Datetime
     * @param User $user User instance
     * @return string Formatted datetime
     */
    public static function formatDateByUserDefaultFormat(Datetime|string $datetime, User $user) {
        if($datetime == '-' || $datetime == '' || $datetime === NULL) {
            return $datetime;
        }

        $format = AppConfiguration::getDefaultDatetimeFormat();

        if($user->getDefaultUserDateTimeFormat() !== NULL) {
            $format = $user->getDefaultUserDateTimeFormat();
        }

        return self::formatDateByFormat($datetime, $format);
    }

    /**
     * Formats datetime by given format
     * 
     * @param string $datetime Datetime
     * @param string $format Datetime format
     * @return string Formatted datetime
     */
    public static function formatDateByFormat(Datetime|string $datetime, string $format) {
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