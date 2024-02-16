<?php

namespace DMS\Components\Process;

/**
 * Common methods for process components
 * 
 * @author Lukas Velek
 */
interface IProcessComponent {
    function work();
    function getWorkflow();
    function getIdAuthor();
}

?>