<?php

namespace DMS\UI\TableBuilder;

/**
 * IBuildable interface allows table elements to be converted (built) to HTML code.
 * 
 * @author Lukas Velek
 * @version 1.1
 */
interface IBuildable {
  function build();
}

?>
