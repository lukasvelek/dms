<?php

namespace App\Entities;

interface IEntityCreatableFromDbRow {
    static function createFromDbRow(mixed $row);
}

?>