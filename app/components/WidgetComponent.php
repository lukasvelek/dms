<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class WidgetComponent extends AComponent {
    public array $homeDashboardWidgets;

    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->homeDashboardWidgets = [];

        $this->createHomeDashboardWidgets();
    }

    private function createHomeDashboardWidgets() {
        $widgetNames = array(
            'documentStats' => 'Document statistics'
        );

        foreach($widgetNames as $name => $text) {
            $this->homeDashboardWidgets[$name] = array('name' => $text, 'code' => $this->{'_' . $name}());
        }
    }

    private function _documentStats() {
        $code = '
            <div class="widget">
                <p class="page-title">Document statistics</p>
            </div>
        ';

        return $code;
    }
}

?>