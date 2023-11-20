<?php

namespace DMS\Components;

use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Helpers\ArrayStringHelper;
use DMS\Models\DocumentModel;

class WidgetComponent extends AComponent {
    private DocumentModel $documentModel;

    public array $homeDashboardWidgets;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;

        $this->homeDashboardWidgets = [];

        $this->createHomeDashboardWidgets();
    }

    private function createHomeDashboardWidgets() {
        $widgetNames = array(
            'documentStats' => 'Document statistics'
        );

        foreach($widgetNames as $name => $text) {
            $this->homeDashboardWidgets[$name] = array('name' => $text, 'code' => $this->{'_' . $name}());
        }
    }

    private function _documentStats() {
        $code = [];
        
        $documentCount = $this->documentModel->getDocumentCount();
        $shreddedDocumentCount = $this->documentModel->getDocumentCount(DocumentStatus::SHREDDED);
        $archivedDocumentCount = $this->documentModel->getDocumentCount(DocumentStatus::ARCHIVED);
        $documentsWaitingForArchivationCount = $this->documentModel->getDocumentCount(DocumentStatus::ARCHIVATION_APPROVED);

        $code[] = '<p><b>Total documents:</b> ' . $documentCount . '</p>';
        $code[] = '<p><b>Shredded documents:</b> ' . $shreddedDocumentCount . '</p>';
        $code[] = '<p><b>Archived documents:</b> ' . $archivedDocumentCount . '</p>';
        $code[] = '<p><b>Documents waiting for archivation:</b> ' . $documentsWaitingForArchivationCount . '</p>';

        return $this->__getTemplate('Document statistics', ArrayStringHelper::createUnindexedStringFromUnindexedArray($code));
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