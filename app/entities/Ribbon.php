<?php

namespace DMS\Entities;

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

    public function getName() {
        return $this->name;
    }

    public function getIdParentRibbon() {
        return $this->idParentRibbon;
    }

    public function getImage() {
        return $this->image;
    }

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

    public function isVisible() {
        return $this->visible;
    }

    public function getPageUrl() {
        return $this->pageUrl;
    }

    public function hasImage() {
        return is_null($this->image) ? false : true;
    }

    public function getCode() {
        return $this->code;
    }

    public function hasParent() {
        return is_null($this->idParentRibbon) ? false : true;
    }

    public function isSystem() {
        return $this->isSystem;
    }

    public function isJS() {
        return $this->isJS ?? false;
    }

    public function getJSMethodName() {
        return $this->jsMethodName;
    }
}

?>