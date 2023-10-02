<?php

namespace DMS\UI;

class LinkBuilder {
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $name;
    
    public function __construct(string $url, string $class, string $name) {
        $this->url = $url;
        $this->class = $class;
        $this->name = $name;
    }

    public function render() {
        $template = $this->getTemplate();

        $template = str_replace('$CLASS$', $this->class, $template);
        $template = str_replace('$URL$', $this->url, $template);
        $template = str_replace('$NAME$', $this->name, $template);

        return $template;
    }

    private function getTemplate() {
        return '<a class="$CLASS$" href="$URL$">$NAME$</a>';
    }

    public static function createLink(string $url, string $name, string $class = 'general-link') {
        $obj = new self('?page=' . $url, $class, $name);
        return $obj->render();
    }

    public static function createAdvLink(array $urlParams, string $name, string $class = 'general-link') {
        $url = '?';

        $i = 0;
        foreach($urlParams as $paramKey => $paramVal) {
            if(($i + 1) == count($urlParams)) {
                $url .= $paramKey . '=' . $paramVal;
            } else {
                $url .= $paramKey . '=' . $paramVal . '&';
            }
            
            $i++;
        }

        $obj = new self($url, $class, $name);
        return $obj->render();
    }
}

?>