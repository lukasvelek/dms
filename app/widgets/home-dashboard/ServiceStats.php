<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Core\ServiceManager;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Models\ServiceModel;
use DMS\Models\UserModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\AWidget;

class ServiceStats extends AWidget {
    private ServiceModel $serviceModel;
    private ServiceManager $serviceManager;
    private UserModel $userModel;

    public function __construct(ServiceModel $serviceModel, ServiceManager $serviceManager, UserModel $userModel) {
        parent::__construct();

        $this->serviceModel = $serviceModel;
        $this->serviceManager = $serviceManager;
        $this->userModel = $userModel;
    }

    public function render() {
        $user = $this->getCurrentUser();

        foreach($this->serviceManager->services as $displayName => $service) {
            $nextRunDate = $this->serviceManager->getNextRunDateForService($service->name);

            $nextRunDate = DatetimeFormatHelper::formatDateByUserDefaultFormat($nextRunDate, $user);

            if($nextRunDate == '-') {
                $this->add('<span style="color: red">' . $displayName . '</span>', 'Next run: ' . $nextRunDate);
            } else {
                $this->add($displayName, 'Next run: ' . $nextRunDate);
            }
        }

        $this->addLink(LinkBuilder::createAdvLink(['page' => 'UserModule:Settings:showServices'], 'Services'));

        return parent::render();
    }

    private function getCurrentUser() {
        if(!isset($_SESSION['id_current_user'])) {
            return null;
        }

        return $this->userModel->getUserById($_SESSION['id_current_user']);
    }
}

?>