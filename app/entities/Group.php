<?php

namespace DMS\Entities;

class Group extends AEntity {
    /**
     * @var string
     */
    private $name;

    public function __construct(int $id, string $dateCreated, string $name) {
        parent::__construct($id, $dateCreated);

        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

?>