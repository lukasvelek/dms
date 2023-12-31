<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Constants\ProcessStatus;
use DMS\Models\ProcessModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\AWidget;

class ProcessStats extends AWidget {
    private ProcessModel $processModel;

    public function __construct(ProcessModel $processModel) {
        parent::__construct();

        $this->processModel = $processModel;
    }

    public function render() {
        $data = $this->processModel->getLastProcessStatsEntry();

        $processCount = $data['total_count'];
        $finishedCount = $data['finished_count'];
        $inProgressCount = $data['in_progress_count'];
        $dateCreated = $data['date_created'];

        $this->add('Total processes', $processCount);
        $this->add('Processes in progress', $inProgressCount);
        $this->add('Finished processes', $finishedCount);

        $this->updateLink(LinkBuilder::createAdvLink(array('page' => 'UserModule:Widgets:updateProcessStats'), 'Update'), $dateCreated);

        return parent::render();
    }
}

?>