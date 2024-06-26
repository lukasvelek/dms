<?php

namespace DMS\Repositories;

use DMS\Authorizators\ActionAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Constants\Metadata\UserLoginBlocksMetadata;
use DMS\Constants\Metadata\UserLoginsMetadata;
use DMS\Constants\Metadata\UserMetadata;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\UserLoginAttemptEntity;
use DMS\Models\UserModel;
use Exception;

class UserRepository extends ARepository {
    public UserModel $userModel;
    private ActionAuthorizator $actionAuthorizator;
    
    private CacheManager $userCache;

    public function __construct(Database $db, Logger $logger, UserModel $userModel, ActionAuthorizator $actionAuthorizator, bool $ajax = false) {
        parent::__construct($db, $logger);
        $this->userModel = $userModel;
        $this->actionAuthorizator = $actionAuthorizator;

        $this->userCache = CacheManager::getTemporaryObject(CacheCategories::USERS, $ajax);
    }

    public function updateUserBlock(int $idBlock, string $dateFrom, ?string $dateTo, string $description) {
        $data = [
            UserLoginBlocksMetadata::DESCRIPTION => $description,
            UserLoginBlocksMetadata::DATE_FROM => $dateFrom,
            UserLoginBlocksMetadata::DATE_TO => $dateTo
        ];

        return $this->userModel->updateUserLoginBlock($idBlock, $data);
    }
    
    public function isUserBlocked(int $idUser) {
        if($this->userModel->getActiveUserLoginBlockByIdUser($idUser) === NULL) {
            return false;
        } else {
            return true;
        }
    }

    public function unblockUser(int $idUser) {
        $data = [
            UserLoginBlocksMetadata::IS_ACTIVE => '0'
        ];

        $blockEntity = $this->userModel->getActiveUserLoginBlockByIdUser($idUser);

        $this->userModel->updateUserLoginBlock($blockEntity->getId(), $data);
    }

    public function blockUser(int $idCallingUser, int $idUser, string $description, string $dateFrom, ?string $dateTo) {
        $data = [
            UserLoginBlocksMetadata::ID_AUTHOR => $idCallingUser,
            UserLoginBlocksMetadata::ID_USER => $idUser,
            UserLoginBlocksMetadata::DESCRIPTION => $description,
            UserLoginBlocksMetadata::DATE_FROM => $dateFrom,
            UserLoginBlocksMetadata::IS_ACTIVE => '1'
        ];

        if($dateTo !== NULL) {
            $data[UserLoginBlocksMetadata::DATE_TO] = $dateTo;
        }

        return $this->userModel->insertUserLoginBlock($data);
    }

    public function getUnsuccessfulLoginAttemptsByDate() {
        $qb = $this->userModel->composeStandardLoginAttemptsQuery();

        $qb ->where('result <> 1')
            ->orderBy('date_created', 'DESC')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $id = $row['id'];
            $dateCreated = $row['date_created'];
            $username = $row['username'];
            $description = $row['description'];
            $result = $row['result'];

            $entities[] = new UserLoginAttemptEntity($id, $dateCreated, $username, $result, $description);
        }

        return $entities;
    }

    public function getLoginAttemptsByDate() {
        $qb = $this->userModel->composeStandardLoginAttemptsQuery();

        $qb ->orderBy('date_created', 'DESC')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $id = $row['id'];
            $dateCreated = $row['date_created'];
            $username = $row['username'];
            $description = $row['description'];
            $result = $row['result'];

            $entities[] = new UserLoginAttemptEntity($id, $dateCreated, $username, $result, $description);
        }

        return $entities;
    }

    public function getExistingUsernames() {
        $qb = $this->userModel->composeStandardUserQuery(['username']);

        $qb->execute();

        $usernames = [];
        while($row = $qb->fetchAssoc()) {
            $usernames[] = $row['username'];
        }

        return $usernames;
    }

    public function checkUsernameExists(string $username) {
        $username = htmlspecialchars($username);
        $sql = "SELECT 1 FROM `users` WHERE `username` = '" . $username . "'";

        $result = $this->db->query($sql);

        if($result->num_rows == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function insertUserLoginAttempt(string $username, int $result, string $description) {
        $data = [
            UserLoginsMetadata::DESCRIPTION => $description,
            UserLoginsMetadata::RESULT => $result,
            UserLoginsMetadata::USERNAME => $username
        ];

        return $this->userModel->insertUserLoginAttempt($data);
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

    public function getUserById(int $id) {
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

    public function getUserByUsername(string $username) {
        return $this->userModel->getUserByUsername($username);
    }
}

?>