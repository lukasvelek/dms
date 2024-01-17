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
}

?>