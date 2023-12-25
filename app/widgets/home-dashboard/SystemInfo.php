<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Core\Application;
use DMS\Widgets\AWidget;

class SystemInfo extends AWidget {
    public function __construct() {
        parent::__construct();
    }

    public function render() {
        $this->add('System version', Application::SYSTEM_VERSION);
        //$this->add('System build date', Application::SYSTEM_BUILD_DATE);

        if(Application::SYSTEM_IS_BETA) {
            $this->add('System build date', '- (this is beta)');
        } else {
            $this->add('System build date', Application::SYSTEM_BUILD_DATE);
        }

        return parent::render();
    }
}

?>