<?php

namespace DMS\Entities;

class ServiceEntity extends AEntity {
    private string $systemName;
    private string $displayName;
    private string $description;
    private bool $isEnabled;
    private bool $isSystem;
    private int $status;
    private ?string $pid;
    
    public function __construct(int $id, string $dateCreated, string $systemName, string $displayName, string $description, bool $isEnabled, bool $isSystem, int $status, ?string $pid) {
        parent::__construct($id, $dateCreated, null);

        $this->systemName = $systemName;
        $this->displayName = $displayName;
        $this->description = $description;
        $this->isEnabled = $isEnabled;
        $this->isSystem = $isSystem;
        $this->status = $status;
        $this->pid = $pid;
    }

    public function getSystemName() {
        return $this->systemName;
    }

    public function getDisplayName() {
        return $this->displayName;
    }

    public function getDescription() {
        return $this->description;
    }

    public function isEnabled() {
        return $this->isEnabled;
    }

    public function isSystem() {
        return $this->isSystem;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getPid() {
        return $this->pid;
    }
}

?>