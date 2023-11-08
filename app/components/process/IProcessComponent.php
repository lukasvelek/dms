<?php

namespace DMS\Components\Process;

interface IProcessComponent {
    function work();
    function getWorkflow();
    function getIdAuthor();
}

?>