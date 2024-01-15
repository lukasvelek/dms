<?php

namespace DMS\Entities;

class DocumentFilter extends AFilter {
    public function __construct(int $id, ?int $idAuthor, string $name, ?string $description, string $sql) {
        parent::__construct($id, $idAuthor, $name, $description, $sql);
    }
}

?>