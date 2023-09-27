<?php

namespace DMS\Panels;

use DMS\Core\TemplateManager;

class Panels {
    public static function createTopPanel() {
        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/toppanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;&nbsp;',
                '<a class="general-link" href="?page=UserModule:HomePage:showHomepage">Home</a>'
            )
        );

        $templateManager->fill($data, $template);

        return $template;
    }

    private static function tm() {
        return TemplateManager::getTemporaryObject();
    }
}

?>