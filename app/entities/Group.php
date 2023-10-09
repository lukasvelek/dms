<?php

namespace DMS\Entities;

class Group extends AEntity {
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $code;

    public function __construct(int $id, string $dateCreated, string $name, ?string $code) {
        parent::__construct($id, $dateCreated);

        $this->name = $name;
        $this->code = $code;
    }

    public function getName() {
        return $this->name;
    }

    public function getCode() {
        return $this->code;
    }
}

?>