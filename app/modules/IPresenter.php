<?php

namespace DMS\Modules;

interface IPresenter {
    function getName();
    function getTitle();
    function performAction(string $name);
}

?>