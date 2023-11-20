<?php

namespace DMS\Helpers;

class DashboardWidgetsHelper {
    public static function render(string $funcName) {
        if(method_exists(self, $funcName)) {
            self::{$funcName}();
        }
    }

    public static function createTotalDocuments() {

    }
}

?>