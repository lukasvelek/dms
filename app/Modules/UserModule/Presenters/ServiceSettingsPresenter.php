<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\ServiceMetadata;
use DMS\Constants\ServiceStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Core\ScriptLoader;
use DMS\Entities\ServiceEntity;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Helpers\GridDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class ServiceSettingsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('ServiceSettings', 'Service settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showNewServiceForm() {
        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'New service',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateNewServiceForm()
        ];

        $this->fill($data, $template);

        return $template;
    }

    protected function processNewServiceForm() {
        global $app;

        $app->flashMessageIfNotIsset(['system_name', 'display_name', 'description']);

        $data = [
            \DMS\Constants\Metadata\ServiceMetadata::SYSTEM_NAME => $this->post('system_name'),
            \DMS\Constants\Metadata\ServiceMetadata::DISPLAY_NAME => $this->post('display_name'),
            \DMS\Constants\Metadata\ServiceMetadata::DESCRIPTION => $this->post('description'),
            \DMS\Constants\Metadata\ServiceMetadata::IS_SYSTEM => '0'
        ];

        if(isset($_POST['is_enabled'])) {
            $data[\DMS\Constants\Metadata\ServiceMetadata::IS_ENABLED] = '1';
        } else {
            $data[\DMS\Constants\Metadata\ServiceMetadata::IS_ENABLED] = '0';
        }

        $app->serviceModel->insertNewService($data);
        $app->logger->info('Inserted new service ' . $this->post('system_name'), __METHOD__);

        $app->flashMessage('New service created', 'success');
        $app->redirect('showServices');
    }

    protected function processEditServiceForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'system_name', 'display_name', 'description']);

        $data = [
            \DMS\Constants\Metadata\ServiceMetadata::SYSTEM_NAME => $this->post('system_name'),
            \DMS\Constants\Metadata\ServiceMetadata::DISPLAY_NAME => $this->post('display_name'),
            \DMS\Constants\Metadata\ServiceMetadata::DESCRIPTION => $this->post('description')
        ];

        if(isset($_POST['is_enabled'])) {
            $data[\DMS\Constants\Metadata\ServiceMetadata::IS_ENABLED] = '1';
        } else {
            $data[\DMS\Constants\Metadata\ServiceMetadata::IS_ENABLED] = '0';
        }

        $app->serviceModel->updateService($this->get('id'), $data);
        $app->flashMessage('Service ' . $data['system_name'] . ' updated', 'success');
        $app->redirect('showServices');
    }

    protected function askToRunService() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');

        $urlConfirm = array(
            'page' => 'UserModule:ServiceSettings:runService',
            'name' => $name
        );

        $urlClose = array(
            'page' => 'UserModule:ServiceSettings:showServices'
        );

        $code = ScriptLoader::confirmUser('Do you want to run service ' . $name . '?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function runService() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');
        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_RUN_DATES);

        if(!array_key_exists($name, $app->serviceManager->services)) {
            $app->flashMessage('Service \'' . $name . '\' (class \\DMS\\Services\\' . $name . ') not found!', 'error');
            $app->redirect('showServices');
        }

        foreach($app->serviceManager->services as $serviceName => $service) {
            if($serviceName == $name) {
                $app->logger->info('Running service \'' . $name . '\'', __METHOD__);

                $app->serviceManager->startBgProcess($name); 

                $cm->invalidateCache();

                break;
            }
        }

        $app->redirect('showServices');
    }

    protected function editService() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');
        
        $values = ArrayHelper::formatArrayData($_POST);

        unset($values['name']);
        unset($values['description']);

        if($name == 'PasswordPolicyService') {
            if(!array_key_exists('password_change_force_administrators', $values)) {
                $values['password_change_force_administrators'] = '0';
            } else {
                $values['password_change_force_administrators'] = '1';
            }

            if(!array_key_exists('password_change_force', $values)) {
                $values['password_change_force'] = '0';
            } else {
                $values['password_change_force'] = '1';
            }
        } else if($name == 'NotificationManagerService') {
            if(!array_key_exists('notification_keep_unseen_service_user', $values)) {
                $values['notification_keep_unseen_service_user'] = '0';
            } else {
                $values['notification_keep_unseen_service_user'] = '1';
            }
        } else if($name == 'LogRotateService') {
            if(!array_key_exists('archive_old_logs', $values)) {
                $values['archive_old_logs'] = '0';
            } else {
                $values['archive_old_logs'] = '1';
            }
        }

        foreach($values as $k => $v) {
            $app->serviceModel->updateServiceConfig($name, $k, $v);
        }

        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_CONFIG);
        $cm->invalidateCache();

        $app->logger->info('Updated configuration for service \'' . $name . '\'', __METHOD__);

        $app->redirect('showServices');
    }

    protected function editServiceForm() {
        global $app;
        
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');
        
        $app->flashMessageIfNotIsset(['name']);
        
        $name = $this->get('name');

        $data = array(
            '$PAGE_TITLE$' => 'Edit service <i>' . $name . '</i> config',
            '$FORM$' => $this->internalCreateEditServiceForm($name),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('showServices', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function editServiceServiceForm() {
        global $app;
        
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');
        
        $app->flashMessageIfNotIsset(['id']);
        
        $id = $this->get('id');
        $service = $app->serviceModel->getServiceById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Edit service <i>' . $service->getDisplayName() . '</i>',
            '$FORM$' => $this->internalCreateEditServiceServiceForm($service),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('showServices', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showServices() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $servicesGrid = '';

        $app->logger->logFunction(function() use (&$servicesGrid) {
            $servicesGrid = $this->internalCreateServicesGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Services',
            '$SETTINGS_GRID$' => $servicesGrid,
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showNewServiceForm'], 'New service') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createLink('refreshServiceRunDates', 'Refresh');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function refreshServiceRunDates() {
        global $app;

        $app->serviceManager->updateRunDates();

        $app->flashMessage('Service grid refreshed');
        $app->redirect('showServices');
    }

    private function internalCreateEditServiceServiceForm(ServiceEntity $service) {
        $fb = new FormBuilder();

        $enabledCheckbox = $fb->createInput()->setType('checkbox')->setName('is_enabled');

        if($service->isEnabled()) {
            $enabledCheckbox->setSpecial('checked');
        }

        $fb ->setAction('?page=UserModule:Settings:processEditServiceForm&id=' . $service->getId())->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('system_name')->setText('System name'))
            ->addElement($fb->createInput()->setType('text')->setName('system_name')->require()->setValue($service->getSystemName())->readonlyIfBoolTrue($service->isSystem()))

            ->addElement($fb->createLabel()->setFor('display_name')->setText('Display name'))
            ->addElement($fb->createInput()->setType('text')->setName('display_name')->require()->setValue($service->getDisplayName())->readonlyIfBoolTrue($service->isSystem()))

            ->addElement($fb->createLabel()->setFor('description')->setText('Description'))
            ->addElement($fb->createInput()->setType('text')->setName('description')->require()->setValue($service->getDescription())->readonlyIfBoolTrue($service->isSystem()))

            ->addElement($fb->createLabel()->setFor('is_enabled')->setText('Enable'))
            ->addElement($enabledCheckbox)

            ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }

    private function internalCreateEditServiceForm(string $name) {
        global $app;

        $service = $app->serviceManager->getServiceByName($name);
        $serviceCfg = $app->serviceModel->getConfigForServiceName($name);

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:editService&name=' . $name);

        foreach($serviceCfg as $key => $value) {
            $fb ->addElement($fb->createLabel()->setText(ServiceMetadata::$texts[$key] . ' (' . $key . ')')->setFor($key));

            switch($key) {
                case ServiceMetadata::FILES_KEEP_LENGTH:
                    $fb
                    ->addElement($fb->createSpecial('<span id="files_keep_length_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax('30')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::PASSWORD_CHANGE_PERIOD:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_period_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('0')->setMax('60')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::PASSWORD_CHANGE_FORCE_ADMINISTRATORS:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_force_administrators_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;

                case ServiceMetadata::PASSWORD_CHANGE_FORCE:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_force_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;

                case ServiceMetadata::NOTIFICATION_KEEP_LENGTH:
                    $fb
                    ->addElement($fb->createSpecial('<span id="notification_keep_length_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('0')->setMax('30')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::NOTIFICATION_KEEP_UNSEEN_SERVICE_USER:
                    $fb
                    ->addElement($fb->createSpecial('<span id="notification_keep_unseen_service_user_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;

                case ServiceMetadata::SERVICE_RUN_PERIOD:
                    $fb
                    ->addElement($fb->createSpecial('<span id="service_run_period_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax('30')->setName($key)->setValue($value))
                    ;

                    break;

                case ServiceMetadata::ARCHIVE_OLD_LOGS:
                    $fb
                    ->addElement($fb->createSpecial('<span id="archive_old_logs_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;
                    break;
            }
        }

        $fb ->loadJSScript('js/EditServiceForm.js')
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }

    private function internalCreateServicesGrid() {
        global $app;

        $serviceManager = $app->serviceManager;
        $serviceModel = $app->serviceModel;
        $user = $app->user;

        $dataCallback = function() use ($serviceModel) {
            return $serviceModel->getAllServicesOrderedByLastRunDate();
        };

        $canRunService = $app->actionAuthorizator->checkActionRight(UserActionRights::RUN_SERVICE);
        $canEditService = $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_SERVICE);
        $canDeleteService = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_SERVICE);

        $gb = new GridBuilder();

        $gb->addColumns(['systemName' => 'System name', 'isEnabled' => 'Enabled', 'displayName' => 'Name', 'description' => 'Description', 'status' => 'Status', 'lastRunDate' => 'Last run date', 'nextRunDate' => 'Next run date']);
        $gb->addOnColumnRender('status', function(ServiceEntity $service) {
            return ServiceStatus::$texts[$service->getStatus()];
        });
        $gb->addOnColumnRender('lastRunDate', function(ServiceEntity $service) use ($serviceManager, $user) {
            $serviceLastRunDate = $serviceManager->getLastRunDateForService($service->getSystemName());
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($serviceLastRunDate, $user);
        });
        $gb->addOnColumnRender('nextRunDate', function(ServiceEntity $service) use ($serviceManager, $user) {
            $serviceNextRunDate = $serviceManager->getNextRunDateForService($service->getSystemName());
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($serviceNextRunDate, $user);
        });
        $gb->addOnColumnRender('isEnabled', function(ServiceEntity $service) {
            return GridDataHelper::renderBooleanValueWithColors($service->isEnabled(), 'Yes', 'No');
        });
        $gb->addDataSourceCallback($dataCallback);
        $gb->addAction(function(ServiceEntity $service) use ($canRunService) {
            $link = '-';
            if($canRunService && $service->isEnabled() === TRUE) {
                $link = LinkBuilder::createAdvLink(array('page' => 'askToRunService', 'name' => $service->getSystemName()), 'Run');
            }
            return $link;
        });
        $gb->addAction(function(ServiceEntity $service) use ($canEditService) {
            $link = '-';
            if($canEditService) {
                $link = LinkBuilder::createAdvLink(array('page' => 'editServiceForm', 'name' => $service->getSystemName()), 'Edit config');
            }
            return $link;
        });
        $gb->addAction(function(ServiceEntity $service) use ($canEditService) {
            $link = '-';
            if($canEditService) {
                $link = LinkBuilder::createAdvLink(array('page' => 'editServiceServiceForm', 'id' => $service->getId()), 'Edit service');
            }
            return $link;
        });
        $gb->addAction(function(ServiceEntity $service) use ($canDeleteService) {
            $link = '-';
            if($canDeleteService && !$service->isSystem()) {
                $link = LinkBuilder::createAdvLink(['page' => 'deleteService', 'name' => $service->getSystemName()], 'Delete');
            }
            return $link;
        });

        return $gb->build();
    }

    private function internalCreateNewServiceForm() {
        $fb = new FormBuilder();

        $fb ->setAction('?page=UserModule:Settings:processNewServiceForm')->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('system_name')->setText('System name'))
            ->addElement($fb->createInput()->setType('text')->setName('system_name')->require())

            ->addElement($fb->createLabel()->setFor('display_name')->setText('Display name'))
            ->addElement($fb->createInput()->setType('text')->setName('display_name')->require())

            ->addElement($fb->createLabel()->setFor('description')->setText('Description'))
            ->addElement($fb->createInput()->setType('text')->setName('description')->require())

            ->addElement($fb->createLabel()->setFor('is_enabled')->setText('Enable'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_enabled')->setSpecial('checked'))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }
}

?>