<?php

namespace DMS\Components;

use DMS\Constants\DocumentStatus;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\Application;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Helpers\ArrayStringHelper;
use DMS\Models\DocumentModel;
use DMS\Models\MailModel;
use DMS\Models\ProcessModel;
use DMS\UI\LinkBuilder;

class WidgetComponent extends AComponent {
    private DocumentModel $documentModel;
    private ProcessModel $processModel;
    private MailModel $mailModel;

    public array $homeDashboardWidgets;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, ProcessModel $processModel, MailModel $mailModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
        $this->processModel = $processModel;
        $this->mailModel = $mailModel;

        $this->homeDashboardWidgets = [];

        $this->createHomeDashboardWidgets();
    }

    private function createHomeDashboardWidgets() {
        $widgetNames = array(
            'documentStats' => 'Document statistics',
            'processStats' => 'Process statistics',
            'processesWaitingForMe' => 'Processes waiting for me',
            'systemInfo' => 'System information',
            'mailInfo' => 'Mail information'
        );

        foreach($widgetNames as $name => $text) {
            $this->homeDashboardWidgets[$name] = array('name' => $text, 'code' => $this->{'_' . $name}());
        }
    }

    private function _mailInfo() {
        $code = [];

        $add = function(string $title, string $text) use (&$code) {
            $code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
        };

        $add('Emails in queue', $this->mailModel->getMailInQueueCount());

        return $this->__getTemplate('Mail information', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
    }

    private function _systemInfo() {
        $code = [];

        $add = function(string $title, string $text) use (&$code) {
            $code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
        };

        $add('System version', Application::SYSTEM_VERSION);
        $add('System build date', Application::SYSTEM_BUILD_DATE);

        return $this->__getTemplate('System information', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
    }

    private function _processesWaitingForMe() {
        $code = [];

        if(!isset($_SESSION['id_current_user'])) {
            return '';
        }

        $idUser = $_SESSION['id_current_user'];

        $waitingForMe = $this->processModel->getProcessesWaitingForUser($idUser);

        if($waitingForMe != null) {
            $i = 0;
            
            foreach($waitingForMe as $process) {
                if($i == 4) {
                    break;
                }

                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:showProcess', 'id' => $process->getId()), 'Process #' . $process->getId() . ' - ' . ProcessTypes::$texts[$process->getType()]);

                $code[] = '<p>' . $link . '</p>';

                $i++;
            }
        } else {
            $code[] = '<p>No processes found</p>';
        }

        return $this->__getTemplate('Processes waiting for me', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
    }

    private function _documentStats() {
        $code = [];
        
        $documentCount = $this->documentModel->getDocumentCountByStatus();
        $shreddedDocumentCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED);
        $archivedDocumentCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED);
        $documentsWaitingForArchivationCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED);
        $newDocumentCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::NEW);

        $add = function(string $title, string $text) use (&$code) {
            $code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
        };

        $add('Total documents', $documentCount);
        $add('Shredded documents', $shreddedDocumentCount);
        $add('Archived documents', $archivedDocumentCount);
        $add('New documents', $newDocumentCount);
        $add('Documents waiting for archivation', $documentsWaitingForArchivationCount);

        return $this->__getTemplate('Document statistics', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
    }

    private function _processStats() {
        $code = [];

        $processCount = $this->processModel->getProcessCountByStatus();
        $finishedProcessCount = $this->processModel->getProcessCountByStatus(ProcessStatus::FINISHED);
        $inProgressProcessCount = $this->processModel->getProcessCountByStatus(ProcessStatus::IN_PROGRESS);

        $add = function(string $title, string $text) use (&$code) {
            $code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
        };

        $add('Total processes', $processCount);
        $add('Processes in progress', $inProgressProcessCount);
        $add('Finished processes', $finishedProcessCount);

        return $this->__getTemplate('Process statistics', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));;
    }

    private function __getTemplate(string $title, string $widgetCode) {
        $code = [];

        $code[] = '<div class="widget">';
        $code[] = '<div class="row">';
        $code[] = '<div class="col-md" id="center">';
        $code[] = '<p class="page-title">' . $title . '</p>';
        $code[] = '</div>';
        $code[] = '</div>';
        $code[] = '<div class="row">';
        $code[] = '<div class="col-md">';
        $code[] = $widgetCode;
        $code[] = '</div>';
        $code[] = '</div>';
        $code[] = '</div>';
        $code[] = '<br>';

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($code);
    }
}

?>