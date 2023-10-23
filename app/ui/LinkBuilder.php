<?php

namespace DMS\UI;

class LinkBuilder {
    private string $url;
    private string $class;
    private string $name;
    private string $style;
    
    public function __construct(string $url, string $class, string $name, string $style = '') {
        $this->url = $url;
        $this->class = $class;
        $this->name = $name;
        $this->style = $style;
    }

    public function render() {
        $hasStyle = false;

        if($this->style != '') {
            $hasStyle = true;
        }

        $template = $this->getTemplate($hasStyle);

        $template = str_replace('$CLASS$', $this->class, $template);
        $template = str_replace('$URL$', $this->url, $template);
        $template = str_replace('$NAME$', $this->name, $template);

        if($this->style != '') {
            $template = str_replace('$STYLE$', $this->style, $template);
        }

        return $template;
    }

    private function getTemplate(bool $style = false) {
        if(!$style) {
            return '<a class="$CLASS$" href="$URL$">$NAME$</a>';
        } else {
            return '<a class="$CLASS$" href="$URL$" style="$STYLE$">$NAME$</a>';
        }
    }

    public static function createLink(string $url, string $name, string $class = 'general-link') {
        $obj = new self('?page=' . $url, $class, $name);
        return $obj->render();
    }

    public static function createAdvLink(array $urlParams, string $name, string $class = 'general-link', string $style = '') {
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

        $obj = new self($url, $class, $name, $style);
        return $obj->render();
    }
}

?>