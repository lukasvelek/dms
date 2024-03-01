<?php

namespace DMS\Services;

use DMS\Constants\UserPasswordChangeStatus;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\GroupUserModel;
use DMS\Models\ServiceModel;
use DMS\Models\UserModel;

class PasswordPolicyService extends AService {
    private const SKIP_ADMIN = true;

    private UserModel $userModel;
    private GroupUserModel $groupUserModel;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, UserModel $userModel, GroupUserModel $groupUserModel) {
        parent::__construct('PasswordPolicyService', $logger, $serviceModel, $cm);

        $this->userModel = $userModel;
        $this->groupUserModel = $groupUserModel;

        $this->loadCfg();
    }

    public function run() {
        $this->startService();

        $maxTime = time() - $this->scfg['password_change_period'];
        $maxDate = date('Y-m-d H:i:s', $maxTime);

        $condition = "WHERE `date_password_changed` < '$maxDate'";
        $users = $this->userModel->getAllUsersMeetingCondition($condition);
        
        $this->log('Found ' . count($users) . ' users who have outdated password.', __METHOD__);

        $force = 'DISABLED';
        $forceAdministrators = 'DISABLED';

        if($this->scfg['password_change_force'] == '1') {
            $force = 'ENABLED';
        }

        if($this->scfg['password_change_force_administrators'] == '1') {
            $forceAdministrators = 'ENABLED';
        }

        $this->log('Forcing general users to change their password is ' . $force, __METHOD__);
        $this->log('Forcing administrators to change their password is ' . $forceAdministrators, __METHOD__);

        $userData = [];

        $warnings = 0;
        $forces = 0;

        $skipUsers = ['service_user'];

        if(self::SKIP_ADMIN) {
            $skipUsers[] = 'admin';
        }

        foreach($users as $user) {
            if(in_array($user->getUsername(), $skipUsers)) {
                continue;
            }

            $passwordChangeStatus = UserPasswordChangeStatus::WARNING;
            $status = UserStatus::ACTIVE;

            if($this->groupUserModel->isIdUserInAdministratorsGroup($user->getId()) === TRUE) {
                if($this->scfg['password_change_force_administrators'] == '1') {
                    $passwordChangeStatus = UserPasswordChangeStatus::FORCE;
                    $status = UserStatus::PASSWORD_UPDATE_REQUIRED;
                    $forces++;
                } else {
                    $warnings++;
                }
            } else {
                if($this->scfg['password_change_force'] == '1') {
                    $passwordChangeStatus = UserPasswordChangeStatus::FORCE;
                    $status = UserStatus::PASSWORD_UPDATE_REQUIRED;
                    $forces++;
                } else {
                    $warnings++;
                }
            }

            $userData[$user->getId()] = array(
                'password_change_status' => $passwordChangeStatus
            );

            if($status != UserStatus::ACTIVE) {
                $userData[$user->getId()]['status'] = $status;
            }
        }

        $this->userModel->beginTran();

        foreach($userData as $id => $data) {
            $this->userModel->updateUser($id, $data);
        }

        $this->userModel->commitTran();

        $this->log('Warned ' . $warnings . ' users', __METHOD__);
        $this->log('Forced ' . $forces . ' users', __METHOD__);

        $this->stopService();
    }
}

?>