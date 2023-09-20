<?php

include('app/dependencies.php');

foreach($dependencies as $dependency) {
    require_once($dependency);
}

?>