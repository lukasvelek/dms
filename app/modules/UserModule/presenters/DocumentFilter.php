<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\DocumentFilter as EntitiesDocumentFilter;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class DocumentFilter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentFilter', 'Document filters');

        $this->getActionNamesFromClass($this);
    }

    protected function unpinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'));

        $idFilter = $this->get('id_filter');
        $ribbon = $app->ribbonModel->getRibbonForIdDocumentFilter($idFilter);

        $app->ribbonModel->deleteRibbonForIdDocumentFilter($idFilter);
        $app->ribbonRightsModel->deleteAllUserRibbonRights($ribbon->getId());
        $app->ribbonRightsModel->deleteAllGroupRibbonRights($ribbon->getId());

        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);

        $rcm->invalidateCache();
        $rucm->invalidateCache();
        $rgcm->invalidateCache();

        unset($rcm, $rucm, $rgcm);

        $app->flashMessage('Successfully unpinned selected filter', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function pinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'));

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $parentRibbon = $app->ribbonModel->getRibbonByCode('documents');

        $data = array(
            'id_parent_ribbon' => $parentRibbon->getId(),
            'name' => $filter->getName(),
            'code' => 'documents.custom_filter.' . $filter->getId(),
            'is_visible' => '1',
            'is_system' => '0',
            'page_url' => '?page=UserModule:Documents:showDocumentsCustomFilter&id_filter=' . $filter->getId()
        );

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        $app->ribbonRightsModel->insertNewUserRibbonRight($idRibbon, $app->user->getId(), array($app->ribbonModel::VIEW => '1', $app->ribbonModel::EDIT => '1', $app->ribbonModel::DELETE => '1'));

        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);

        $rcm->invalidateCache();
        $rucm->invalidateCache();
        $rgcm->invalidateCache();

        unset($rcm, $rucm, $rgcm);

        $app->flashMessage('Successfully pinned selected filter', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function deleteFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = $this->get('id_filter');

        $app->filterModel->deleteDocumentFilter($idFilter);
        
        $app->flashMessage('Document filter #' . $idFilter . ' deleted', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function showFilterResults() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Document filter #' . $idFilter . ' results',
            '$LINKS$' => array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilters'), '<-')
            ),
            '$FILTER_GRID$' => $this->internalCreateFilterResultsGrid($filter),
            '$BULK_ACTION_CONTROLLER$' => ''
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSingleFilter() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-form.html');

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Filter <i>' . $filter->getName() . '</i>',
            '$LINKS$' => array(
                LinkBuilder::createLink('UserModule:DocumentFilter:showFilters', '<-')
            ),
            '$FILTER_FORM$' => $this->internalCreateEditFilterForm($filter)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showFilters() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document filters',
            '$LINKS$' => [],
            '$FILTER_GRID$' => $this->internalCreateStandardFilterGrid(),
            '$BULK_ACTION_CONTROLLER$' => ''
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_FILTER)) {
            $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showNewFilterForm'), 'New filter');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'id_filter'), true, array('page' => 'UserModule:DocumentFilter:showNewFilterForm'));
        $idFilter = $this->get('id_filter');

        $data = [];
        $data['name'] = $this->post('name');

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = $this->post('description');
        }

        if(isset($_POST['filter_sql'])) {
            $data['filter_sql'] = $this->post('filter_sql');
        }

        if(isset($_POST['has_ordering'])) {
            $data['has_ordering'] = '1';
        }

        $app->filterModel->updateDocumentFilter($data, $idFilter);

        $app->flashMessage('Filter #' . $idFilter . ' updated successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function processNewFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name'), true, array('page' => 'UserModule:DocumentFilter:showNewFilterForm'));

        $data = [];
        $data['name'] = $this->post('name');

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = $this->post('description');
        }

        if(isset($_POST['filter_sql'])) {
            $data['filter_sql'] = $this->post('filter_sql');
        }

        if(isset($_POST['has_ordering'])) {
            $data['has_ordering'] = '1';
        }

        $data['id_author'] = $app->user->getId();

        $app->filterModel->insertNewDocumentFilter($data);

        $app->flashMessage('Filter created successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function showNewFilterForm() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New filter',
            '$LINKS$' => '',
            '$FILTER_FORM$' => $this->internalCreateNewFilterForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateNewFilterForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processNewFilterForm')
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description'))
            
            ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql'))

            ->addElement($fb->createLabel()->setText('SQL query has ordering?')->setFor('has_ordering'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('has_ordering'))
            
            ->addElement($fb->createSubmit('Create filter'));

        return $fb->build();
    }

    private function internalCreateStandardFilterGrid() {
        global $app;
        
        $userModel = $app->userModel;
        $actionAuthorizator = $app->actionAuthorizator;
        $filterModel = $app->filterModel;
        $user = $app->user;

        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);
        
        $dataSourceCallback = function() use ($filterModel, $actionAuthorizator, $user) {
            $seeSystemFilters = $actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTERS);
            $seeOtherUsersFilters = $actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTERS);

            if(!$seeSystemFilters && !$seeOtherUsersFilters) {
                return $filterModel->getAllDocumentFiltersForIdUser($user->getId());
            } else {
                return $filterModel->getAllDocumentFilters($seeSystemFilters, $seeOtherUsersFilters, $user->getId());
            }
        };

        $canSeeOtherUsersFilterResults = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTER_RESULTS);
        $canSeeSystemFilterResults = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTER_RESULTS);
        $canEditOtherUsersFilters = $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_OTHER_USERS_FILTER);
        $canEditSystemFilter = $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_SYSTEM_FILTER);

        $idPinnedFilters = $app->ribbonModel->getIdRibbonsForDocumentFilters();

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'description' => 'Description', 'idAuthor' => 'Author']);
        $gb->addOnColumnRender('idAuthor', function(\DMS\Entities\DocumentFilter $filter) use ($userModel, $ucm) {
            if(is_null($filter->getIdAuthor())) {
                return 'System';
            } else {
                $user = $ucm->loadUserByIdFromCache($filter->getIdAuthor());

                if(is_null($user)) {
                    $user = $userModel->getUserById($filter->getIdAuthor());

                    $ucm->saveUserToCache($user);
                }

                return $user->getFullname();
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($user, $canSeeOtherUsersFilterResults, $canSeeSystemFilterResults) {
            if(!is_null($filter->getIdAuthor())) {
                if($filter->getIdAuthor() == $user->getId()) {
                    return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                } else {
                    if($canSeeOtherUsersFilterResults) {
                        return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                    }
                }
            } else {
                if($canSeeSystemFilterResults) {
                    return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                }
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($user, $canEditOtherUsersFilters, $canEditSystemFilter) {
            if(!is_null($filter->getIdAuthor())) {
                if($filter->getIdAuthor() == $user->getId()) {
                    return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                } else {
                    if($canEditOtherUsersFilters) {
                        return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                    }
                }
            } else {
                if($canEditSystemFilter) {
                    return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                }
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($user, $canSeeOtherUsersFilterResults) {
            if(!is_null($filter->getIdAuthor())) {
                if($filter->getIdAuthor() == $user->getId()) {
                    return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:deleteFilter', 'id_filter' => $filter->getId()), 'Delete');
                } else {
                    if($canSeeOtherUsersFilterResults) {
                        return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:deleteFilter', 'id_filter' => $filter->getId()), 'Delete');
                    }
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($idPinnedFilters) {
            if(in_array($filter->getId(), $idPinnedFilters)) {
                return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:unpinFilter', 'id_filter' => $filter->getId()), 'Unpin');
            } else {
                return LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:pinFilter', 'id_filter' => $filter->getId()), 'Pin');
            }
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function internalCreateEditFilterForm(EntitiesDocumentFilter $filter) {
        $fb = FormBuilder::getTemporaryObject();

        if($filter->hasOrdering()) {
            $hasOrderingTrue = ' checked';
        } else {
            $hasOrderingTrue = '';
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processEditFilterForm&id_filter=' . $filter->getId())
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require()->setValue($filter->getName()))

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description')->setText($filter->getDescription() ?? ''))
        
            ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql')->setText($filter->getSql()))

            ->addElement($fb->createLabel()->setText('SQL query has ordering?')->setFor('has_ordering'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('has_ordering')->setSpecial($hasOrderingTrue))
            
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }

    private function internalCreateFilterResultsGrid(EntitiesDocumentFilter $filter) {
        return '
            <script type="text/javascript">
                loadDocumentsCustomFilter("' . $filter->getId() . '");
            </script>
            <table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }
}

?>