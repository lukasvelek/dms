<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;
use DMS\Repositories\UserRepository;

class UserLoginBlockingManagerService extends AService {
    private UserRepository $userRepository;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, UserRepository $userRepository) {
        parent::__construct('UserLoginBlockingManagerService', $logger, $serviceModel, $cm);

        $this->userRepository = $userRepository;
    }

    public function run() {
        $this->startService();

        $loginBlocks = $this->userRepository->userModel->getActiveUserLoginBlocks();

        $this->log(sprintf('Found %d login blockings.', count($loginBlocks)), __METHOD__);

        foreach($loginBlocks as $loginBlock) {
            $dateFrom = $loginBlock->getDateFrom();
            $dateTo = $loginBlock->getDateTo();
            $isActive = $loginBlock->isActive();

            if($dateTo !== NULL) {
                if(strtotime($dateTo) < time()) {
                    // it has already finished
                    $this->log('Found login block for user #' . $loginBlock->getIdUser() . ' that has already ended but is still active. Deactivating...', __METHOD__);
                    $this->userRepository->unblockUser($loginBlock->getIdUser());
                }
            }
        }

        $this->stopService();
    }
}

?>