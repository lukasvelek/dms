<?php

namespace DMS\Entities;

/**
 * Ribbon entity
 * 
 * @author Lukas Velek
 */
class Ribbon extends AEntity {
    private string $name;
    private ?int $idParentRibbon;
    private ?string $image;
    private ?string $title;
    private bool $visible;
    private string $pageUrl;
    private string $code;
    private bool $isSystem;
    private bool $isJS;
    private string $jsMethodName;

    /**
     * Class constructor
     * 
     * @param int $id Ribbon ID
     * @param string $name Ribbon name
     * @param null|string $title Ribbon title or null
     * @param null|int $idParentRibbon Parent ribbon ID or null
     * @param null|string $image Ribbon image or null
     * @param bool $visible Is ribbon visible
     * @param string $pageUrl Ribbon page URL
     * @param string $code Ribbon code
     * @param bool Is ribbon system
     */
    public function __construct(int $id, string $name, ?string $title, ?int $idParentRibbon, ?string $image, bool $visible, string $pageUrl, string $code, bool $isSystem) {
        parent::__construct($id, null, null);

        $this->name = $name;

        $this->idParentRibbon = $idParentRibbon;
        $this->image = $image;
        $this->visible = $visible;
        $this->pageUrl = $pageUrl;
        $this->code = $code;
        $this->isSystem = $isSystem;
        $this->title = $title;
        $this->isJS = false;
        $this->jsMethodName = '';
        
        if(str_starts_with($this->pageUrl, 'js.')) {
            $this->isJS = true;
            $this->jsMethodName = substr($this->pageUrl, 3);

            if(str_contains($this->jsMethodName, '$ID_PARENT_RIBBON$')) {
                $this->jsMethodName = str_replace('$ID_PARENT_RIBBON$', $this->idParentRibbon ?? '0', $this->jsMethodName);
            }

            if(str_contains($this->jsMethodName, '$ID_RIBBON$')) {
                $this->jsMethodName = str_replace('$ID_RIBBON$', $this->id, $this->jsMethodName);
            }
        }
    }

    /**
     * Returns ribbon name
     * 
     * @return string Ribbon name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns parent ribbon ID
     * 
     * @return null|int Parent ribbon ID or null
     */
    public function getIdParentRibbon() {
        return $this->idParentRibbon;
    }

    /**
     * Returns ribbon image
     * 
     * @return null|string Ribbon image or null
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Returns ribbon title
     * 
     * @param bool $showReal If true and ribbon title is null then null is returned
     * @return string Ribbon name or ribbon title
     */
    public function getTitle(bool $showReal = false) {
        if(is_null($this->title)) {
            if($showReal) {
                return $this->title;
            } else {
                return $this->name;
            }
        } else {
            return $this->title;
        }
    }

    /**
     * Returns whether the ribbon is visible
     * 
     * @return bool True if ribbon is visible or false if not
     */
    public function isVisible() {
        return $this->visible;
    }

    /**
     * Returns ribbon page URL
     * 
     * @return string Ribbon page URL
     */
    public function getPageUrl() {
        return $this->pageUrl;
    }

    /**
     * Returns whether the ribbon has iamge or not
     * 
     * @return bool True if ribbon has image or false if not
     */
    public function hasImage() {
        return is_null($this->image) ? false : true;
    }

    /**
     * Returns ribbon code
     * 
     * @return string Ribbon code
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Returns whether the ribbon has parent or not
     * 
     * @return bool True if ribbon has parent or false if not
     */
    public function hasParent() {
        return is_null($this->idParentRibbon) ? false : true;
    }

    /**
     * Returns whether the ribbon is system or not
     * 
     * @return bool True if ribbon is system or false if not
     */
    public function isSystem() {
        return $this->isSystem;
    }

    /**
     * Returns whether the ribbon is JavaScript or not
     * 
     * @return bool True if ribbon is JavaScript or false if not
     */
    public function isJS() {
        return $this->isJS ?? false;
    }

    /**
     * Returns ribbon JavaScript method name
     * 
     * @return string Ribbon JavaScript method name
     */
    public function getJSMethodName() {
        return $this->jsMethodName;
    }
}

?>