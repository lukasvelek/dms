<?php

namespace DMS\Repositories;

use DMS\Authorizators\ActionAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Constants\Metadata\UserMetadata;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\UserModel;
use Exception;

class UserRepository extends ARepository {
    private UserModel $userModel;
    private ActionAuthorizator $actionAuthorizator;
    
    private CacheManager $userCache;

    public function __construct(Database $db, Logger $logger, UserModel $userModel, ActionAuthorizator $actionAuthorizator) {
        parent::__construct($db, $logger);
        $this->userModel = $userModel;
        $this->actionAuthorizator = $actionAuthorizator;

        $this->userCache = CacheManager::getTemporaryObject(CacheCategories::USERS);
    }

    public function getActiveUsersIDs() {
        $qb = $this->userModel->composeStandardUserQuery(['id']);
        $qb ->where($qb->getColumnInValues(UserMetadata::STATUS, [UserStatus::ACTIVE]))
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $row['id'];
        }

        return $users;
    }

    public function deactiveUser(int $id) {
        if($this->actionAuthorizator->canEditUser() !== TRUE) {
            throw new Exception("Current user cannot edit other users");
        }

        return $this->userModel->updateUserStatus($id, UserStatus::INACTIVE);
    }

    public function activateUser(int $id) {
        if($this->actionAuthorizator->canEditUser() !== TRUE) {
            throw new Exception('Current user cannot edit other users');
        }

        return $this->userModel->updateUserStatus($id, UserStatus::ACTIVE);
    }

    public function getUserEntityByID(int $id) {
        $valFromCache = $this->userCache->loadUserByIdFromCache($id);

        $user = null;
        if($valFromCache === NULL) {
            $user = $this->userModel->getUserById($id);

            $this->userCache->saveUserToCache($user);
        } else {
            $user = $valFromCache;
        }
        
        return $user;
    }
}

?>