<?php

namespace DMS\Modules;

interface IPresenter {
    function getName();
    function performAction(string $name);
    function setModule(IModule $module);
    function getModule();
}

?>