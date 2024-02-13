<?php

namespace DMS\Components;

use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Helpers\ArrayStringHelper;
use DMS\Models\DocumentModel;
use DMS\Models\MailModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\HomeDashboard\DocumentStats;
use DMS\Widgets\HomeDashboard\MailInfo;
use DMS\Widgets\HomeDashboard\Notifications;
use DMS\Widgets\HomeDashboard\ProcessStats;
use DMS\Widgets\HomeDashboard\SystemInfo;

class WidgetComponent extends AComponent {
    private DocumentModel $documentModel;
    private ProcessModel $processModel;
    private MailModel $mailModel;
    private NotificationModel $notificationModel;

    public array $homeDashboardWidgets;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, ProcessModel $processModel, MailModel $mailModel, NotificationModel $notificationModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
        $this->processModel = $processModel;
        $this->mailModel = $mailModel;
        $this->notificationModel = $notificationModel;

        $this->homeDashboardWidgets = [];
        
        $this->createHomeDashboardWidgetList();
    }

    public function render(string $widgetName) {
        return $this->homeDashboardWidgets[$widgetName]['render']();
    }

    private function createHomeDashboardWidgetList() {
        $this->homeDashboardWidgets = array(
            'documentStats' => array(
                'text' => 'Document statistics',
                'render' => function() {
                    $ds = new DocumentStats($this->documentModel);

                    return $this->__getTemplate('Document statistics', $ds->render());
                }
            ),
            'processStats' => array(
                'text' => 'Process statistics',
                'render' => function() {
                    $ps = new ProcessStats($this->processModel);

                    return $this->__getTemplate('Process statistics', $ps->render());
                }
            ),
            'mailInfo' => array(
                'text' => 'Mail information',
                'render' => function() {
                    $mi = new MailInfo($this->mailModel);

                    return $this->__getTemplate('Mail information', $mi->render());
                }
            ),
            'systemInfo' => array(
                'text' => 'System information',
                'render' => function() {
                    $si = new SystemInfo();

                    return $this->__getTemplate('System information', $si->render());
                }
            ),
            'processesWaitingForMe' => array(
                'text' => 'Processes waiting for me',
                'render' => function() {
                    $code = [];

                    if(!isset($_SESSION['id_current_user'])) {
                        return '';
                    }

                    $idUser = $_SESSION['id_current_user'];

                    $count = 5;

                    $waitingForMe = null;

                    $this->logger->logFunction(function() use (&$waitingForMe, $idUser, $count) {
                        $waitingForMe = $this->processModel->getProcessesWaitingForUser($idUser, $count);
                    }, __METHOD__);

                    if($waitingForMe != null) {
                        foreach($waitingForMe as $process) {
                            /*if($i == 4) {
                                break;
                            }*/

                            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:showProcess', 'id' => $process->getId()), 'Process #' . $process->getId() . ' - ' . ProcessTypes::$texts[$process->getType()]);

                            $code[] = '<p>' . $link . '</p>';
                        }
                    } else {
                        $code[] = '<p>No processes found</p>';
                    }

                    return $this->__getTemplate('Processes waiting for me', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
                }
            ),
            'notifications' => array(
                'text' => 'Notifications',
                'render' => function() {
                    $nf = new Notifications($this->notificationModel);

                    return $this->__getTemplate('Notifications', $nf->render());
                }
            )
        );
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