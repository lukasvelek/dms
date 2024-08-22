<?php

namespace App\Entities;

class UserEntity implements IEntityCreatableFromDbRow {
    public int $USER_ID;
    public string $USERNAME;
    public ?string $FULLNAME;
    public ?string $EMAIL;
    public bool $IS_ACTIVE;
    public bool $EMAIL_ENABLED;
    public string $DATE_CREATED;

    public function __construct(int $USER_ID, string $USERNAME, ?string $FULLNAME, ?string $EMAIL, bool $IS_ACTIVE, bool $EMAIL_ENABLED, string $DATE_CREATED) {
        $this->USER_ID = $USER_ID;
        $this->USERNAME = $USERNAME;
        $this->FULLNAME = $FULLNAME;
        $this->EMAIL = $EMAIL;
        $this->IS_ACTIVE = $IS_ACTIVE;
        $this->EMAIL_ENABLED = $EMAIL_ENABLED;
        $this->DATE_CREATED = $DATE_CREATED;
    }

    public static function createFromDbRow(mixed $row) {
        return new self($row['USER_ID'], $row['USERNAME'], $row['FULLNAME'], $row['EMAIL'], $row['IS_ACTIVE'], $row['EMAIL_ENABLED'], $row['DATE_CREATED']);
    }
}

?>