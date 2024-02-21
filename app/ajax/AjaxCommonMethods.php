<?php

use DMS\Helpers\FormDataHelper;

function get(string $key, bool $escape = true) {
    return FormDataHelper::get($key, $escape);
}

function post(string $key, bool $escape = true) {
    return FormDataHelper::post($key, $escape);
}

?>