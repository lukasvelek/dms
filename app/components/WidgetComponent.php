<?php

namespace DMS\Components;

use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Core\ServiceManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\Models\DocumentModel;
use DMS\Models\MailModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessModel;
use DMS\Models\ServiceModel;
use DMS\Models\UserModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\HomeDashboard\DocumentStats;
use DMS\Widgets\HomeDashboard\MailInfo;
use DMS\Widgets\HomeDashboard\Notifications;
use DMS\Widgets\HomeDashboard\ProcessStats;
use DMS\Widgets\HomeDashboard\ServiceStats;
use DMS\Widgets\HomeDashboard\SystemInfo;

/**
 * Component used with widgets
 * 
 * @author Lukas Velek
 */
class WidgetComponent extends AComponent {
    private DocumentModel $documentModel;
    private ProcessModel $processModel;
    private MailModel $mailModel;
    private NotificationModel $notificationModel;
    private ServiceModel $serviceModel;
    private ServiceManager $serviceManager;
    private UserModel $userModel;

    public array $homeDashboardWidgets;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param ProcessModel $processModel ProcessModel instance
     * @param MailModel $mailModel MailModel instance
     * @param NotificationModel $notificationModel NotificationModel instance
     * @param ServiceModel $serviceModel ServiceModel instance
     * @param ServiceManager $serviceManager ServiceManager instance
     * @param UserModel $userModel UserModel instance
     */
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, ProcessModel $processModel, MailModel $mailModel, NotificationModel $notificationModel, ServiceModel $serviceModel, ServiceManager $serviceManager, UserModel $userModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
        $this->processModel = $processModel;
        $this->mailModel = $mailModel;
        $this->notificationModel = $notificationModel;
        $this->serviceModel = $serviceModel;
        $this->serviceManager = $serviceManager;
        $this->userModel = $userModel;

        $this->homeDashboardWidgets = [];
        
        $this->createHomeDashboardWidgetList();
    }

    /**
     * Returns generated widget HTML code
     * 
     * @param string $widgetName Widget name
     * @return string Widget HTML code
     */
    public function render(string $widgetName) {
        return $this->homeDashboardWidgets[$widgetName]['render']();
    }

    /**
     * Generates widgets HTML codes
     */
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
            ),
            'service_stats' => array(
                'text' => 'Service stats',
                'render' => function() {
                    $ss = new ServiceStats($this->serviceModel, $this->serviceManager, $this->userModel);

                    return $this->__getTemplate('Service stats', $ss->render());
                }
            )
        );
    }

    /**
     * Returns a widget template
     * 
     * @param string $title Widget title
     * @param string $widgetCode Widget HTML code
     * @return string Widget HTML code
     */
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