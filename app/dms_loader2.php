<?php

$dependencies = [];

function loadDependencies(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dms_loader.php',
        $dir . '\\install',
        $dir . '\\Ajax',
        $dir . '\\PHPMailer',
        $dir . '\\dms_loader2.php'
    );

    $extensionsToSkip = array(
        'html',
        'md',
        'js',
        'png',
        'gif',
        'jpg',
        'svg',
        'sql'
    );

    foreach($content as $c) {
        $filenameParts = explode('.', $c);
        
        /* SKIP CERTAIN EXTENSIONS */
        if(in_array($filenameParts[count($filenameParts) - 1], $extensionsToSkip)) {
            continue;
        }

        $c = $dir . '\\' . $c;

        if(!in_array($c, $skip)) {
            if(!is_dir($c)) {
                // je soubor

                $dependencies[] = $c;
            } else {
                // je slozka

                loadDependencies($dependencies, $c);
            }
        }
    }
}

/**
 * Sorts dependencies based on their type:
 *  1. Interfaces
 *  2. Abstract classes
 *  3. General classes
 * 
 * @param array $dependencies Array of dependencies
 */
function sortDependencies(array &$dependencies) {
    $interfaces = [];
    $classes = [];
    $abstractClasses = [];

    foreach($dependencies as $dependency) {
        $filenameArr = explode('\\', $dependency);
        $filename = $filenameArr[count($filenameArr) - 1];

        if($filename[0] == 'A' && ctype_upper($filename[1])) {
            $abstractClasses[] = $dependency;
        } else if($filename[0] == 'I' && ctype_upper($filename[1])) {
            if(getNestLevel($dependency) > 5) {
                $interfaces[] = $dependency;
            } else {
                $interfaces = array_merge([$dependency], $interfaces);
            }
        } else {
            $classes[] = $dependency;
        }
    }

    $dependencies = array_merge($interfaces, $abstractClasses, $classes);
}

/**
 * Returns the nest level of the dependency
 * 
 * @param string $dependecyPath Dependency path
 * @return int Nest level
 */
function getNestLevel(string $dependencyPath) {
    return count(explode('\\', $dependencyPath));
}

function loadDependencies2() {
    $classes = get_declared_classes();
    $interfaces = get_declared_interfaces();

    $temp = [];
    foreach($classes as $class) {
        $mainpart = explode('\\', $class)[0];

        if($mainpart === 'DMS') {
            $temp[] = $class;
        }
    }
    $classes = $temp;

    $temp = [];
    foreach($interfaces as $interface) {
        $mainpart = explode('\\', $interface)[0];

        if($mainpart === 'DMS') {
            $temp[] = $interface;
        }
    }
    $interfaces = $temp;

    /*var_dump($classes);
    var_dump($interfaces);*/

    foreach($classes as $class) {
        constructClass($class);
    }
}

function constructClass(string $className) {
    $constructor = new ReflectionMethod($className, '__construct');
    $parameters = $constructor->getParameters();

    foreach($parameters as $parameter) {
        if(!in_array($parameter->getType(), ['int', 'string', 'bool', 'float', '?int', '?string', '?bool', '?float'])) {
            $dependenceClass = (string) $parameter->getType();

            constructClass($dependenceClass);
        }
    }
}

loadDependencies($dependencies, __DIR__);
sortDependencies($dependencies);

foreach($dependencies as $dependency) {
    require_once($dependency);
}

loadDependencies2();

die();

?>