<?php

namespace DMS\Entities;

class UserLoginAttemptEntity extends AEntity {
    private string $username;
    private int $result;
    private string $description;

    public function __construct(int $id, string $dateCreated, string $username, int $result, string $description) {
        parent::__construct($id, $dateCreated, null);

        $this->username = $username;
        $this->result = $result;
        $this->description = $description;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getResult() {
        return $this->result;
    }

    public function getDescription() {
        return $this->description;
    }
}

?>