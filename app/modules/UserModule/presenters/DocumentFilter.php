<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\Metadata\DocumentFilterMetadata;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\Metadata\RibbonMetadata;
use DMS\Constants\MetadataInputType;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\DocumentFilter as EntitiesDocumentFilter;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class DocumentFilter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentFilter', 'Document filters');

        $this->getActionNamesFromClass($this);
    }

    protected function unpinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'), true, ['page' => 'showFilters']);

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
        $app->redirect('showFilters');
    }

    protected function pinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'), true, ['page' => 'showFilters']);

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $parentRibbon = $app->ribbonModel->getRibbonByCode('documents');

        $data = array(
            RibbonMetadata::ID_PARENT_RIBBON => $parentRibbon->getId(),
            RibbonMetadata::NAME => $filter->getName(),
            RibbonMetadata::CODE => 'documents.custom_filter.' . $filter->getId(),
            RibbonMetadata::IS_VISIBLE => '1',
            RibbonMetadata::IS_SYSTEM => '0',
            RibbonMetadata::PAGE_URL => '?page=UserModule:Documents:showDocumentsCustomFilter&id_filter=' . $filter->getId()
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
        $app->redirect('showFilters');
    }

    protected function deleteFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'showFilters'));

        $idFilter = $this->get('id_filter');

        $app->filterModel->deleteDocumentFilter($idFilter);
        
        $app->flashMessage('Document filter #' . $idFilter . ' deleted', 'success');
        $app->redirect('showFilters');
    }

    protected function showFilterResults() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'showFilters'));

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Document filter #' . $idFilter . ' results',
            '$LINKS$' => array(
                LinkBuilder::createAdvLink(array('page' => 'showFilters'), '&larr;')
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

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'showFilters'));

        $idFilter = $this->get('id_filter');
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Filter <i>' . $filter->getName() . '</i>',
            '$LINKS$' => array(
                LinkBuilder::createLink('showFilters', '&larr;')
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
            $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'showNewFilterForm'), 'New filter');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'id_filter'), true, array('page' => 'showNewFilterForm'));
        $idFilter = $this->get('id_filter');

        $data = [];
        $data[DocumentFilterMetadata::NAME] = $this->post('name');

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data[DocumentFilterMetadata::DESCRIPTION] = $this->post('description');
        }

        if(isset($_POST['filter_sql'])) {
            $data[DocumentFilterMetadata::FILTER_SQL] = $this->post('filter_sql');
        }

        if(isset($_POST['has_ordering'])) {
            $data[DocumentFilterMetadata::HAS_ORDERING] = '1';
        }

        $app->filterModel->updateDocumentFilter($data, $idFilter);

        $app->flashMessage('Filter #' . $idFilter . ' updated successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('showFilters');
    }

    protected function processNewFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name'), true, array('page' => 'showNewFilterForm'));

        $data = [];
        $data['name'] = $this->post('name');

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = $this->post('description');
        }

        $sql = "SELECT * FROM `documents`";

        $metadata = [
            DocumentMetadata::ID_AUTHOR,
            DocumentMetadata::ID_OFFICER,
            DocumentMetadata::ID_MANAGER,
            DocumentMetadata::RANK,
            DocumentMetadata::STATUS,
            DocumentMetadata::SHREDDING_STATUS,
            'document_form'
        ];
        $textMetadata = $metadata;

        // append custom metadata
        $dbMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
        foreach($dbMetadata as $dbm) {
            if($dbm->getIsSystem() == TRUE) continue;
            if($dbm->getInputType() == MetadataInputType::SELECT_EXTERNAL) continue;

            if($dbm->getInputType() != MetadataInputType::NUMBER) {
                $textMetadata[] = $dbm->getName();
            }

            $metadata[] = $dbm->getName();
        }

        $createCondition = function(string $column, string $value, string $operation) {
            $op = '';
            switch($operation) {
                case 'equal':
                    $op = '=';
                    break;

                case 'not_equal':
                    $op = '<>';
                    break;

                case 'bigger':
                    $op = '>';
                    break;

                case 'bigger_equal':
                    $op = '>=';
                    break;

                case 'smaller':
                    $op = '<';
                    break;

                case 'smaller_equal':
                    $op = '<=';
                    break;
            }

            $sql = '`' . $column . '` ' . $op . " '" . $value . "'";

            return $sql;
        };

        $checkMetadata = [];
        foreach($metadata as $md) {
            $operation = '';
            if(isset($_POST[$md . '_operation'])) {
                $operation = $this->post($md . '_operation');
            }

            $value = $this->post($md);
            $andor = '';

            if(in_array($md, $textMetadata)) {
                if(!in_array($md, ['equal', 'not_equal'])) {
                    $operation = 'equal';
                }
            }

            if(isset($_POST[$md . '_andor'])) {
                $andor = strtoupper($this->post($md . '_andor'));
            }

            if($value != 'null' && ($md != 'shredding_status' && $value != '5')) {
                $checkMetadata[] = [
                    'name' => $md,
                    'operation' => $operation,
                    'value' => $value,
                    'andor' => $andor
                ];
            }
        }

        if(count($checkMetadata) > 0) {
            $sql .= ' WHERE ';
        }

        foreach($checkMetadata as $cm) {
            if($cm['name'] == 'document_form') {
                if($cm['value'] == 'IS NULL') {
                    $sql .= "(`file` IS NULL OR `file` = '') " . $cm['andor'];
                } else {
                    $sql .= "(`file` IS NOT NULL AND `file` <> '') " . $cm['andor'];
                }
            } else {
                $sql .= $createCondition($cm['name'], $cm['value'], $cm['operation']) . ' ' . $cm['andor'] . ' ';
            }
        }

        $data['filter_sql'] = $sql;

        $data['id_author'] = $app->user->getId();

        $app->filterModel->insertNewDocumentFilter($data);

        $app->flashMessage('Filter created successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('showFilters');
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
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processNewFilterForm')
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description'));

        // default metadata
        $operations = [
            [
                'value' => 'equal',
                'text' => '='
            ],
            [
                'value' => 'not_equal',
                'text' => '!='
            ],
            [
                'value' => 'bigger',
                'text' => '>'
            ],
            [
                'value' => 'bigger_equal',
                'text' => '>='
            ],
            [
                'value' => 'smaller',
                'text' => '<'
            ],
            [
                'value' => 'smaller_equal',
                'text' => '<='
            ]
        ];

        $dbUsers = $app->userModel->getAllUsersPresentInDocuments();
        $users = [['value' => 'null', 'text' => '-', 'selected' => 'selected']];
        foreach($dbUsers as $dbu) {
            $users[] = [
                'value' => $dbu->getId(),
                'text' => $dbu->getFullname()
            ];
        }

        foreach(['id_author' => 'Author', 'id_officer' => 'Current officer', 'id_manager' => 'Manager'] as $name => $text) {
            $fb ->addElement($fb->createLabel()->setText($text)->setFor($name))
                ->addElement($fb->createSelect()->setName($name . '_operation')->addOptionsBasedOnArray($operations))
                ->addElement($fb->createSelect()->setName($name)->addOptionsBasedOnArray($users))
            ;

            $fb->addElement($fb->createSelect()->setName($name . '_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));
        }

        // rank, status, shredding status
        $ranks = [['value' => 'null', 'text' => '-', 'selected' => 'selected']];
        foreach(DocumentRank::$texts as $k => $v) {
            $ranks[] = [
                'value' => $k,
                'text' => $v
            ];
        }

        $statuses = [['value' => 'null', 'text' => '-', 'selected' => 'selected']];
        foreach(DocumentStatus::$texts as $k => $v) {
            $statuses[] = [
                'value' => $k,
                'text' => $v
            ];
        }
        
        $shreddingStatuses = [];
        foreach(DocumentShreddingStatus::$texts as $k => $v) {
            $tmp = [
                'value' => $k,
                'text' => $v
            ];

            if($v == '-') {
                $tmp['selected'] = 'selected';
            }

            $shreddingStatuses[] = $tmp;
        }

        $fb ->addElement($fb->createLabel()->setText('Document rank')->setFor('rank'))
            ->addElement($fb->createSelect()->setName('rank_operation')->addOptionsBasedOnArray($operations))
            ->addElement($fb->createSelect()->setName('rank')->addOptionsBasedOnArray($ranks))
        ;

        $fb->addElement($fb->createSelect()->setName('rank_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));

        $fb ->addElement($fb->createLabel()->setText('Document status')->setFor('status'))
            ->addElement($fb->createSelect()->setName('status_operation')->addOptionsBasedOnArray($operations))
            ->addElement($fb->createSelect()->setName('status')->addOptionsBasedOnArray($statuses))
        ;

        $fb->addElement($fb->createSelect()->setName('status_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));

        $fb ->addElement($fb->createLabel()->setText('Document shredding status')->setFor('shredding_status'))
            ->addElement($fb->createSelect()->setName('shredding_status_operation')->addOptionsBasedOnArray($operations))
            ->addElement($fb->createSelect()->setName('shredding_status')->addOptionsBasedOnArray($shreddingStatuses))
        ;

        $fb->addElement($fb->createSelect()->setName('shredding_status_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));

        $fb ->addElement($fb->createLabel()->setText('Document form')->setFor('document_form'))
            ->addElement($fb->createSelect()->setName('document_form_operation')->addOptionsBasedOnArray($operations)->disable())
            ->addElement($fb->createSelect()->setName('document_form')->addOptionsBasedOnArray([['value' => 'null', 'text' => '-', 'selected' => 'selected'], ['value' => 'IS NULL', 'text' => 'Physical'], ['value' => 'IS NOT NULL', 'text' => 'Electronic']]))
        ;

        // custom metadata
        $dbMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
        $first = true;
        $i = 0;
        foreach($dbMetadata as $dbm) {
            if($dbm->getIsSystem() == TRUE) continue;
            if($dbm->getInputType() == MetadataInputType::SELECT_EXTERNAL) continue;

            $dbValues = $app->metadataModel->getAllValuesForIdMetadata($dbm->getId());
            $values = [['value' => 'null', 'text' => '-']];
            foreach($dbValues as $dbv) {
                $values[] = [
                    'value' => $dbv->getValue(),
                    'text' => $dbv->getName()
                ];
            }

            if($first === TRUE) {
                $fb->addElement($fb->createSelect()->setName('shredding_status_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));
                $first = false;
            }

            $fb ->addElement($fb->createLabel()->setText($dbm->getText())->setFor($dbm->getName()))
                ->addElement($fb->createSelect()->setName($dbm->getName() . '_operation')->addOptionsBasedOnArray($operations))
                ->addElement($fb->createSelect()->setName($dbm->getName())->addOptionsBasedOnArray($values))
            ;

            if(($i + 1) < count($dbMetadata)) {
                $fb->addElement($fb->createSelect()->setName($dbm->getName() . '_andor')->addOptionsBasedOnArray([['value' => 'and', 'text' => 'and'], ['value' => 'or', 'text' => 'or']]));
            }
        }

        $fb ->addElement($fb->createSubmit('Create'));

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
                    return LinkBuilder::createAdvLink(array('page' => 'showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                } else {
                    if($canSeeOtherUsersFilterResults) {
                        return LinkBuilder::createAdvLink(array('page' => 'showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                    }
                }
            } else {
                if($canSeeSystemFilterResults) {
                    return LinkBuilder::createAdvLink(array('page' => 'showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                }
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($user, $canEditOtherUsersFilters, $canEditSystemFilter) {
            if(!is_null($filter->getIdAuthor())) {
                if($filter->getIdAuthor() == $user->getId()) {
                    return LinkBuilder::createAdvLink(array('page' => 'showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                } else {
                    if($canEditOtherUsersFilters) {
                        return LinkBuilder::createAdvLink(array('page' => 'showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                    }
                }
            } else {
                if($canEditSystemFilter) {
                    return LinkBuilder::createAdvLink(array('page' => 'showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                }
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($user, $canSeeOtherUsersFilterResults) {
            if(!is_null($filter->getIdAuthor())) {
                if($filter->getIdAuthor() == $user->getId()) {
                    return LinkBuilder::createAdvLink(array('page' => 'deleteFilter', 'id_filter' => $filter->getId()), 'Delete');
                } else {
                    if($canSeeOtherUsersFilterResults) {
                        return LinkBuilder::createAdvLink(array('page' => 'deleteFilter', 'id_filter' => $filter->getId()), 'Delete');
                    }
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(\DMS\Entities\DocumentFilter $filter) use ($idPinnedFilters) {
            if(in_array($filter->getId(), $idPinnedFilters)) {
                return LinkBuilder::createAdvLink(array('page' => 'unpinFilter', 'id_filter' => $filter->getId()), 'Unpin');
            } else {
                return LinkBuilder::createAdvLink(array('page' => 'pinFilter', 'id_filter' => $filter->getId()), 'Pin');
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