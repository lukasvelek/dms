<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;
use DMS\Models\GroupUserModel;
use DMS\Models\UserModel;

/**
 * DocumentAuthorizator checks if a user is allowed to perform an action with metadata.
 * 
 * @author Lukas Velek
 */
class MetadataAuthorizator extends AAuthorizator {
    private UserModel $userModel;
    private GroupUserModel $groupUserModel;

    /**
     * The MetadataAuthorizator constructor creates an object
     */
    public function __construct(Database $db, Logger $logger, ?User $user, UserModel $userModel, GroupUserModel $groupUserModel) {
        parent::__construct($db, $logger, $user);

        $this->userModel = $userModel;
        $this->groupUserModel = $groupUserModel;
    }

    /**
     * Checks if user is allowed to view metadata
     * 
     * @param int $idUser User ID
     * @param int $idMetadata Metadata ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user can view metadata and false if not
     */
    public function canUserViewMetadata(int $idUser, int $idMetadata, bool $checkCache = true) {
        if($this->groupUserModel->isIdUserInAdministratorsGroup($idUser)) {
            return true;
        }

        $row = $this->getRightRow($idUser, $idMetadata, 'view', $checkCache);

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    /**
     * Checks if user is allowed to edit metadata
     * 
     * @param int $idUser User ID
     * @param int $idMetadata Metadata ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user can edit metadata and false if not
     */
    public function canUserEditMetadata(int $idUser, int $idMetadata, bool $checkCache = true) {
        if($this->groupUserModel->isIdUserInAdministratorsGroup($idUser)) {
            return true;
        }

        $row = $this->getRightRow($idUser, $idMetadata, 'edit', $checkCache);

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    /**
     * Checks if user is allowed to view metadata values
     * 
     * @param int $idUser User ID
     * @param int $idMetadata Metadata ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user can view metadata values and false if not
     */
    public function canUserViewMetadataValues(int $idUser, int $idMetadata, bool $checkCache = true) {
        if($this->groupUserModel->isIdUserInAdministratorsGroup($idUser)) {
            return true;
        }

        $row = $this->getRightRow($idUser, $idMetadata, 'view_values', $checkCache);

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    /**
     * Checks if user is allowed to edit metadata values
     * 
     * @param int $idUser User ID
     * @param int $idMetadata Metadata ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user can edit metadata values and false if not
     */
    public function canUserEditMetadataValues(int $idUser, int $idMetadata, bool $checkCache = true) {
        if($this->groupUserModel->isIdUserInAdministratorsGroup($idUser)) {
            return true;
        }
        
        $row = $this->getRightRow($idUser, $idMetadata, 'edit_values', $checkCache);

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    /**
     * Returns a right row from the database
     * 
     * @param int $idUser User ID
     * @param int $idMetadata Metadata ID
     * @param string $key Metadata key
     * @param bool $checkCache True if cache should be checked and false if not
     * @return mixed Row from database
     */
    private function getRightRow(int $idUser, int $idMetadata, string $key, bool $checkCache = true) {
        $qb = $this->qb(__METHOD__);

        if($checkCache) {
            $cm = CacheManager::getTemporaryObject(CacheCategories::METADATA);

            $valFromCache = $cm->loadMetadataRight($idUser, $idMetadata, $key);

            if($valFromCache != null) {
                return $valFromCache;
            }

            $row = $qb->select('*')
                      ->from('user_metadata_rights')
                      ->where('id_user=:id_user')
                      ->andWhere('id_metadata=:id_metadata')
                      ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_metadata' => $idMetadata
                      ))
                      ->execute()
                      ->fetchSingle();

            if(!is_null($row)) {
                if(!array_key_exists($key, $row)) {
                    return false;
                } else {
                    $result = $row[$key];
                }
            } else {
                return false;
            }

            if(!is_null($result)) {
                $cm->saveMetadataRight($idUser, $idMetadata, $key, $result);
            }
        } else {
            $row = $qb->select('*')
                      ->from('user_metadata_rights')
                      ->where('id_user=:id_user')
                      ->andWhere('id_metadata=:id_metadata')
                      ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_metadata' => $idMetadata
                      ))
                      ->execute()
                      ->fetchSingle();

            $result = $row[$key];
        }

        return $result;
    }
}

?>