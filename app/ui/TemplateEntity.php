<?php

namespace App\UI;

class TemplateEntity {
    private array $__elements;
    private string $__templateCode;

    public function __construct(string $templateCode) {
        $this->__elements = [];
        $this->__templateCode = $templateCode;
    }

    public function render() {
        foreach($this->__elements as $e) {
            $originalE = $e;

            $e = strtoupper($e);

            $e = '$' . $e . '$';

            $this->__templateCode = str_replace($e, $this->$originalE, $this->__templateCode);
        }

        return $this->__templateCode;
    }

    public function __set(string $name, mixed $value) {
        $this->$name = $value;

        $this->__elements[] = $name;
    }

    public function __get(string $name) {
        if(array_key_exists($name, $this->__elements)) {
            return $this->$name;
        } else {
            return null;
        }
    }
}

?>