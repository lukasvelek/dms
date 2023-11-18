<?php

namespace DMS\UI\FormBuilder;

/**
 * Special form element is used to pass custom code to the form
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class Special implements IBuildable {
    public string $script;

    /**
     * The special form element constructor sets the class variables to the passed variable.
     * 
     * @param string $code Code
     * @return self
     */
    public function __construct(string $code) {
        $this->script = trim($code);

        return $this;
    }
    
    /**
     * Converts the special class to HTML code
     * 
     * @return self
     */
    public function build() {
        return $this;
    }
}

?>