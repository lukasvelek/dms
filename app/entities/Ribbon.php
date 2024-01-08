<?php

namespace DMS\Entities;

class Ribbon extends AEntity {
    private string $name;
    private ?int $idParentRibbon;
    private ?string $image;
    private string $title;
    private bool $visible;
    private string $pageUrl;
    private string $code;

    public function __construct(int $id, string $name, ?string $title, ?int $idParentRibbon, ?string $image, bool $visible, string $pageUrl, string $code) {
        parent::__construct($id, null);

        $this->name = $name;

        if($title == null) {
            $this->title = $name;
        }

        $this->idParentRibbon = $idParentRibbon;
        $this->image = $image;
        $this->visible = $visible;
        $this->pageUrl = $pageUrl;
        $this->code = $code;
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

    public function getTitle() {
        return $this->title;
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
}

?>