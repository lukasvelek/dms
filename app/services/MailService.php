<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Core\MailManager;
use DMS\Models\MailModel;
use DMS\Models\ServiceModel;

class MailService extends AService {
    private MailModel $mailModel;
    private MailManager $mailManager;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, MailModel $mailModel, MailManager $mailManager) {
        parent::__construct('MailService', 'Service responsible for sending emails', $logger, $serviceModel, $cm);

        $this->mailModel = $mailModel;
        $this->mailManager = $mailManager;
    }

    public function run() {
        $this->startService();
        
        $mails = $this->mailModel->getMailQueue();

        $this->log('Found ' . $mails->num_rows . ' emails to be sent', __METHOD__);

        foreach($mails as $mail) {
            $recipient = $mail['recipient'];
            $body = $mail['body'];
            $title = $mail['title'];

            $this->mailManager->sendEmail($recipient, $title, $body);
            $this->mailModel->deleteFromQueue($mail['id']);
        }

        $this->stopService();
    }
}

?>