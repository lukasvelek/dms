<?php

namespace DMS\Entities;

/**
 * Common filter class
 * 
 * @author Lukas Velek
 */
abstract class AFilter extends AEntity {
    private string $sql;
    private string $name;
    private ?string $description;
    private ?int $idAuthor;
    private bool $hasOrdering;

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
    protected function __construct(int $id, ?int $idAuthor, string $name, ?string $description, string $sql, bool $hasOrdering = false) {
        parent::__construct($id, null, null);

        $this->sql = $sql;
        $this->name = $name;
        $this->description = $description;
        $this->idAuthor = $idAuthor;
        $this->hasOrdering = $hasOrdering;
    }

    /**
     * Returns SQL string
     * 
     * @return string SQL string
     */
    public function getSql() {
        return $this->sql;
    }

    /**
     * Returns filter name
     * 
     * @return string Filter name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns filter description
     * 
     * @return null|string Filter description or null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns filter author ID
     * 
     * @return null|int Filter author ID or null
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }

    /**
     * Returns whether the filter has own ordering
     * 
     * @return bool True if the filter has its own ordering or false if not
     */
    public function hasOrdering() {
        return $this->hasOrdering;
    }
}

?>