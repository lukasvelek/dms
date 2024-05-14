<?php

namespace DMS\Entities;

use DMS\Constants\DocumentLockType;

class DocumentLockEntity extends AEntity
{
    private int $idDocument;
    private ?int $idProcess;
    private ?int $idUser;
    private int $status;
    private string $description;

    public function __construct(int $id, string $dateCreated, string $dateUpdated, int $idDocument, ?int $idProcess, ?int $idUser, int $status, string $description)
    {
        parent::__construct($id, $dateCreated, $dateUpdated);

        $this->idDocument = $idDocument;
        $this->idProcess = $idProcess;
        $this->idUser = $idUser;
        $this->status = $status;
        $this->description = $description;
    }

    public function getIdDocument()
    {
        return $this->idDocument;
    }

    public function getIdProcess()
    {
        return $this->idProcess;
    }

    public function getIdUser()
    {
        return $this->idUser;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getType()
    {
        if($this->idProcess !== NULL && $this->idUser === NULL)
        {
            return DocumentLockType::PROCESS_LOCK;
        }
        else if($this->idProcess === NULL && $this->idUser !== NULL)
        {
            return DocumentLockType::USER_LOCK;
        }
        else
        {
            return NULL;
        }
    }
}

?>