<?php

namespace DMS\UI;

class LinkBuilder {
    private string $url;
    private string $class;
    private string $name;
    private string $style;
    private ?string $imgPath;
    
    public function __construct(string $url, string $class, string $name, string $style = '', ?string $imgPath = NULL) {
        $this->url = $url;
        $this->class = $class;
        $this->name = $name;
        $this->style = $style;
        $this->imgPath = $imgPath;
    }

    public function render() {
        $hasStyle = false;

        if($this->style != '') {
            $hasStyle = true;
        }

        if($this->imgPath == NULL) {
            $template = $this->getTemplate($hasStyle);

            $template = str_replace('$CLASS$', $this->class, $template);
            $template = str_replace('$URL$', $this->url, $template);
            $template = str_replace('$NAME$', $this->name, $template);

            if($this->style != '') {
                $template = str_replace('$STYLE$', $this->style, $template);
            }
        } else {
            $template = $this->getImgTemplate();

            if($this->name != '') {
                $this->name = ' ' . $this->name;
            }

            $template = str_replace('$CLASS$', $this->class, $template);
            $template = str_replace('$URL$', $this->url, $template);
            $template = str_replace('$IMG_PATH$', $this->imgPath ?? '-', $template);
            $template = str_replace('$NAME$', $this->name, $template);
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

    private function getImgTemplate() {
        return '<a class="$CLASS$" href="$URL$"><img src="$IMG_PATH$" width="28px" height="28px" loading="lazy">$NAME$</a>';
    }

    public static function createImgLink(string $url, string $name, string $imgPath, string $class = 'general-link') {
        $obj = new self('?page=' . $url, $class, $name, '', $imgPath);
        return $obj->render();
    }

    public static function createImgAdvLink(array $urlParams, string $name, string $imgPath, string $class = 'general-link') {
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

        $obj = new self($url, $class, $name, '', $imgPath);
        return $obj->render();
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