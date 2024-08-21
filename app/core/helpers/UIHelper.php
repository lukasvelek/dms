<?php

namespace App\Core\Helpers;

use App\Exceptions\Core\UndefinedModuleException;
use App\UI\AModule;

class UIHelper {
    private array $cfg;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
    }

    public function checkPresenterExists(string $name, AModule $module) {
        $this->createPresenterInstance($name, $module);
    }

    public function createPresenterInstance(string $name, AModule $module) {
        $className = '\\App\\UI\\' . $module->name . '\\Presenters\\' . $name . 'Presenter';

        return new $className();
    }

    public function checkModuleExists(string $name) {
        $this->createModuleInstance($name);
    }

    public function createModuleInstance(string $name) {
        $path = $this->createModulePath($name);

        if(is_dir($path) && is_file($path . '\\' . $name . '.php')) {
            $className = '\\App\\UI\\' . $name . '\\' . $name;

            return new $className();
        } else {
            throw new UndefinedModuleException('\\App\\UI\\' . $name . '\\' . $name);
        }
    }

    private function createModulePath(string $name) {
        return $this->cfg['APP_REAL_DIR'] . 'app\\ui\\' . $name;
    }
}

?>