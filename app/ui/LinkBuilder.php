<?php

namespace DMS\UI;

/**
 * LinkBuilder classes is used to create HTML '<a>' links using PHP.
 * 
 * @author Lukas Velek
 * @version 1.0
 */
class LinkBuilder {
    private string $url;
    private string $class;
    private string $name;
    private string $style;
    private ?string $imgPath;
    private ?int $imgWidth;
    
    /**
     * The default constructor that does nothing more than save passed arguments to the class variables.
     * 
     * @param string $url Link URL
     * @param string $class Link CSS class
     * @param string $name Link text
     * @param string $style Link custom CSS style definition
     * @param null|string $imgPath Link image
     */
    public function __construct(string $url, string $class, string $name, string $style = '', ?string $imgPath = NULL, ?int $imgWidth = NULL) {
        $this->url = $url;
        $this->class = $class;
        $this->name = $name;
        $this->style = $style;
        $this->imgPath = $imgPath;
        $this->imgWidth = $imgWidth;
    }

    /**
     * Converts the class variables to final HTML code
     * 
     * @return string HTML code
     */
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
            $template = $this->getImgTemplate($this->imgWidth ?? 32);

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

    /**
     * Returns the link template based on passed parameters.
     * 
     * @param bool $style True if custom style will be used and false if not
     * @return string HTML link template
     */
    private function getTemplate(bool $style = false) {
        if(!$style) {
            return '<span class="$CLASS$" style="cursor: pointer" onclick="location.replace(\'$URL$\');">$NAME$</span>';
        } else {
            return '<span class="$CLASS$" style="cursor: pointer; $STYLE$" onclick="location.replace(\'$URL$\');">$NAME$</span>';
        }
    }

    /**
     * Returns the image link template.
     * 
     * @return string HTML image link template
     */
    private function getImgTemplate(int $width) {
        return '<img src="$IMG_PATH$" width="' . $width . '" loading="lazy"><span class="$CLASS$" style="cursor: pointer" onclick="location.replace(\'$URL$\');">$NAME$</span>';
    }

    /**
     * Static function to get basic image link.
     * 
     * @param string $url Link URL
     * @param string $name Link text
     * @param string $imgPath Link image path
     * @param string $class Link CSS class
     * @return string HTML code
     */
    public static function createImgLink(string $url, string $name, string $imgPath, string $class = 'general-link', bool $pureUrl = false) {
        $obj = new self(($pureUrl ? '' : '?page=') . $url, $class, $name, '', $imgPath);
        return $obj->render();
    }

    /**
     * Static function to get advanced image link.
     * 
     * @param array $urlParams Link URL params that will be parsed into a single string URL link
     * @param string $name Link text
     * @param string $imgPath Link image path
     * @param string $class Link CSS class
     * @return string HTML code
     */
    public static function createImgAdvLink(array $urlParams, string $name, string $imgPath, string $class = 'general-link', ?int $imgWidth = NULL) {
        $obj = new self(self::createURL($urlParams), $class, $name, '', $imgPath, $imgWidth);
        return $obj->render();
    }

    /**
     * Static function to get basic text link.
     * 
     * @param string $url Link URL
     * @param string $name Link text
     * @param string $class Link CSS class
     * @return string HTML code
     */
    public static function createLink(string $url, string $name, string $class = 'general-link', bool $pureUrl = false) {
        if(!$pureUrl) {
            $params = array(
                'page' => $url
            );

            $obj = new self(self::createURL($params), $class, $name, '');
        } else {
            $link = ($pureUrl ? '' : '?page=') . $url;

            if(!str_contains($link, 'id_ribbon')) {
                if(isset($_GET['id_ribbon'])) {
                    $link .= '&id_ribbon=' . $_GET['id_ribbon'];
                }  
            }

            $obj = new self($link, $class, $name);
        }

        return $obj->render();
    }

    /**
     * Static function to get advanced image link.
     * 
     * @param array $urlParams Link URL params that will be parsed into a single string URL link
     * @param string $name Link text
     * @param string $class Link CSS class
     * @param string $style Link CSS custom style
     * @return string HTML code
     */
    public static function createAdvLink(array $urlParams, string $name, string $class = 'general-link', string $style = '') {
        $obj = new self(self::createURL($urlParams), $class, $name, $style);
        return $obj->render();
    }

    private static function createURL(array $urlParams) {
        $url = '?';

        $i = 0;
        foreach($urlParams as $paramKey => $paramVal) {
            if($paramKey == 'page' && isset($_GET['page'])) {
                $urlPage = htmlspecialchars($_GET['page']);
                $urlPageParts = explode(':', $urlPage);
                if($paramVal == ':') {
                    $paramVal = $urlPage;
                } else {
                    $vals = explode(':', $paramVal);

                    switch(count($vals)) {
                        case 1:
                            // only action
                            $paramVal = $urlPageParts[0] . ':' . $urlPageParts[1] . ':' . $paramVal;
                            break;

                        case 2:
                            // presenter & action
                            $paramVal = $urlPageParts[0] . ':' . $paramVal;
                            break;
                    }   
                }
            }

            if(($i + 1) == count($urlParams)) {
                $url .= $paramKey . '=' . $paramVal;
            } else {
                $url .= $paramKey . '=' . $paramVal . '&';
            }
            
            $i++;
        }

        if(!array_key_exists('id_ribbon', $urlParams)) {
            if(isset($_GET['id_ribbon'])) {
                $url .= '&id_ribbon=' . $_GET['id_ribbon'];
            }
        }

        return $url;
    }
}

?>