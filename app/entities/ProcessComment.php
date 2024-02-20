<?php

namespace DMS\Entities;

/**
 * Process comment entity
 * 
 * @author Lukas Velek
 */
class ProcessComment extends Comment {
    private int $idProcess;

    /**
     * Class constructor
     * 
     * @param int $id Comment ID
     * @param string $dateCreated Date created
     * @param int $idAuthor Author ID
     * @param string $text Comment text
     * @param int $idProcess Process ID
     */
    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text, int $idProcess) {
        parent::__construct($id, $dateCreated, $idAuthor, $text);

        $this->idProcess = $idProcess;
    }

    /**
     * Returns process ID
     * 
     * @return int Process ID
     */
    public function getIdProcess() {
        return $this->idProcess;
    }
}

?>