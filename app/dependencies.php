<?php

/**
 * 'dependencyName' => array ('dependencyPath', array('dependency constructor dependencies'))
 */

$dependencies = array(
    'Application' => array('path' => 'app/core/Application.php', 'dependency' => array()),
    'IDbQueriable' => array('path' => 'app/core/db/qb/IDbQueriable.php', 'dependency' => array()),
    'ILoggerCallable' => array('path' => 'app/core/db/qb/ILoggerCallable.php', 'dependency' => array()),
    'FileManager' => array('path' => 'app/core/FileManager.php', 'dependency' => array('log.log_folder', 'log.cache_folder')),
    'LogCategoryEnum' => array('path' => 'app/core/logger/LogCategoryEnum.php', 'dependency' => array()),
    'Logger' => array('path' => 'app/core/logger/Logger.php', 'dependency' => array('FileManager')),
    'Database' => array('path' => 'app/core/db/Database.php', 'dependency' => array('log.db_server', 'log.db_user', 'log.db_pass', 'log.db_name', 'Logger')),
    'QueryBuilder' => array('path' => 'app/core/db/qb/QueryBuilder.php', 'dependency' => array('Database', 'Logger'))
);

?>