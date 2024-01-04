<?php

namespace DMS\Modules;

interface IModule {
    function getName();
    function getTitle();
    function getPresenterByName(string $name);
    function setPresenter(IPresenter $presenter);
}

?>