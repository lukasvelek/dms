<?php

namespace DMS\Modules;

interface IModule {
    function getName();
    function getPresenterByName(string $name);
    function setPresenter(IPresenter $presenter);
}

?>