<?php

use App\Core\DatabaseConnection;
use App\Core\Logger;
use App\Core\Managers\CacheManager;
use QueryBuilder\QueryBuilder;

abstract class ARepository {
    private DatabaseConnection $db;
    private Logger $logger;
    protected CacheManager $cacheManager;

    protected function __construct(DatabaseConnection $db, Logger $logger, CacheManager $cacheManager) {
        $this->db = $db;
        $this->logger = $logger;
        $this->cacheManager = $cacheManager;
    }

    public function qb(string $method) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }

    public function query(string $sql, array $params = []) {
        return $this->db->query($sql, $params);
    }
}

?>