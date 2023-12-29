<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Models\MailModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\AWidget;

class MailInfo extends AWidget {
    private MailModel $mailModel;

    public function __construct(MailModel $mailModel) {
        parent::__construct();

        $this->mailModel = $mailModel;
    }

    public function render() {
        $mailCount = $this->mailModel->getMailInQueueCount();

        $this->add('Emails in queue', $mailCount);
        $this->add('Queue list', LinkBuilder::createLink('UserModule:MailQueue:showQueue', 'Open'));

        return parent::render();
    }
}

?>