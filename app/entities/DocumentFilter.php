<?php

namespace DMS\Entities;

/**
 * Document filter entity
 */
class DocumentFilter extends AFilter {
    /**
     * Class constructor
     * 
     * @param int $id Entity ID
     * @param null|int $idAuthor Author ID or null
     * @param string $name Filter name
     * @param null|string $description Filter description or null
     * @param string $sql Filter SQL string
     * @param bool $hasOrdering Filter has own ordering
     */
    public function __construct(int $id, ?int $idAuthor, string $name, ?string $description, string $sql, bool $hasOrdering = false) {
        parent::__construct($id, $idAuthor, $name, $description, $sql, $hasOrdering);
    }
}

?>