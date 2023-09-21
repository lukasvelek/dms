<?php

namespace DMS\UI\FormBuilder;

class Special implements IBuildable {
    /**
     * @var string
     */
    public $script;

    public function __construct(string $code) {
        $this->script = trim($code);
    }
    
    public function build() {
        return $this;
    }
}

?>