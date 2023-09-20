<?php

namespace DMS\Core;

class Application {
    /**
     * @var array
     */
    public $cfg;

    /**
     * @var string
     */
    private $currentUrl;

    /**
     * @var 
     */
    private $user;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
    }
}

?>