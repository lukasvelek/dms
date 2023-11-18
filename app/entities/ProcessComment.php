<?php

namespace DMS\Entities;

class ProcessComment extends Comment {
    private int $idProcess;

    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text, int $idProcess) {
        parent::__construct($id, $dateCreated, $idAuthor, $text);

        $this->idProcess = $idProcess;
    }

    public function getIdProcess() {
        return $this->idProcess;
    }
}

?>