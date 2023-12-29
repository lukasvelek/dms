<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Constants\ProcessStatus;
use DMS\Models\ProcessModel;
use DMS\Widgets\AWidget;

class ProcessStats extends AWidget {
    private ProcessModel $processModel;

    public function __construct(ProcessModel $processModel) {
        parent::__construct();

        $this->processModel = $processModel;
    }

    public function render() {
        $processes = $this->processModel->getAllProcesses();

        $processCount = count($processes);
        $finishedCount = $inProgressCount = 0;

        foreach($processes as $process) {
            switch($process->getStatus()) {
                case ProcessStatus::IN_PROGRESS:
                    $inProgressCount++;
                    break;

                case ProcessStatus::FINISHED:
                    $finishedCount++;
                    break;
            }
        }

        $this->add('Total processes', $processCount);
        $this->add('Processes in progress', $inProgressCount);
        $this->add('Finished processes', $finishedCount);

        return parent::render();
    }
}

?>