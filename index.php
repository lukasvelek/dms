<?php

use App\Core\Application;
use App\Core\AppLoader;

session_start();

try {
    if(!file_exists('config.local.php')) {
        throw new RuntimeException('Config file could not be found!', 9999);
    }

    require_once('config.local.php');
    require_once('app/app_loader.php');

    $app = new Application($cfg);
    echo($app->run());
} catch(Exception $e) {
    echo($e);
}

?>