<?php

namespace QueryBuilder;

/**
 * ILoggerCallable is an interface that must be implemented by a class that allows logging.
 * 
 * @version 1.0
 * @author Lukas Velek
 */
interface ILoggerCallable {
    /**
     * The default logging function for logging SQL commands
     */
    function sql(string $sql, string $method);
}

?>