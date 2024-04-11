<?php

namespace DMS\Services;

use DMS\Components\ProcessComponent;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ProcessModel;
use DMS\Models\ServiceModel;
use DMS\Repositories\UserAbsenceRepository;

class UserSubstitutionProcessService extends AService {
    private ProcessComponent $processComponent;
    private UserAbsenceRepository $userAbsenceRepository;
    private ProcessModel $processModel;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, ProcessComponent $processComponent, UserAbsenceRepository $userAbsenceRepository) {
        parent::__construct('UserSubstitutionProcessService', $logger, $serviceModel, $cm);

        $this->processComponent = $processComponent;
        $this->userAbsenceRepository = $userAbsenceRepository;
        $this->processModel = $this->processComponent->getProcessModel();
    }

    public function run() {
        $this->startService();

        $absentUsers = $this->userAbsenceRepository->getIdAbsentUsers();

        $this->log('Found ' . count($absentUsers) . ' absent users', __METHOD__);

        foreach($absentUsers as $user) {
            $processes = $this->processModel->getProcessesWaitingForUser($user);

            $this->log('Found ' . count($processes) . ' process for absent user #' . $user, __METHOD__);

            foreach($processes as $process) {
                $idSubstitute = $this->userAbsenceRepository->getIdSubstituteForIdUser($user);

                if($idSubstitute !== NULL) {
                    $this->log('Updating process #' . $process->getId() . ' workflow ' . $process->getWorkflowStatus() . ' from user #' . $user . ' to user #' . $idSubstitute, __METHOD__);
                    $this->processComponent->updateProcessWorkflowUser($process->getId(), $process->getWorkflowStatus(), $idSubstitute); // process update and notification
                } else {
                    $this->log('User #' . $user . ' has set no substitute. Skipping...', __METHOD__);
                    break;
                }
            }
        }

        $this->stopService();
    }
}

?>