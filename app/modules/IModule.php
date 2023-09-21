<?php

namespace DMS\Modules;

interface IModule {
    function getName();
    function getPresenterByName(string $name);
    function setPresenter(IPresenter $presenter);
    function addComponent(string $name, object $object);
    function getComponent(string $name);
}

?>