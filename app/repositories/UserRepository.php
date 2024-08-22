<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Logger;
use App\Core\Managers\CacheManager;
use App\Entities\UserEntity;
use ARepository;

class UserRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger, CacheManager $cacheManager) {
        parent::__construct($db, $logger, $cacheManager);
    }

    public function getUserById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('USERS')
            ->where('USER_ID = ?', [$id]);

        $c = $this->cacheManager->loadCache($id, function() use ($qb) {
            $userRow = $qb  ->execute()
                            ->fetch();

            return $userRow;
        }, CacheManager::NS_USERS, __METHOD__);

        $user = UserEntity::createFromDbRow($c);
    }
}

?>