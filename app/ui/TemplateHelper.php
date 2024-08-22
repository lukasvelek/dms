<?php

namespace App\UI;

use App\Exceptions\Core\TemplateDoesNotExistException;

class TemplateHelper {
    public function __construct() {}

    public function loadPresenterActionTemplate(string $actionName, string $presenterName, string $moduleName) {
        $path = __DIR__ . '\\' . $moduleName . '\\Presenters\\templates\\' . $presenterName . '\\' . $actionName . '.html';

        if(!file_exists($path)) {
            throw new TemplateDoesNotExistException($path);
        }

        $templateContent = file_get_contents($path);

        return new TemplateEntity($templateContent);
    }

    public function loadModuleTemplate(string $moduleName) {
        $path = __DIR__ . '\\' . $moduleName . '\\templates\\common.html';

        if(!file_exists($path)) {
            throw new TemplateDoesNotExistException($path);
        }

        $templateContent = file_get_contents($path);

        return new TemplateEntity($templateContent);
    }
}

?>