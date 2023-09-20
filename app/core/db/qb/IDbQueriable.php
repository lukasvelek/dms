<?php

namespace QueryBuilder;

interface IDbQueriable {
    function query(string $sql);
}

?>