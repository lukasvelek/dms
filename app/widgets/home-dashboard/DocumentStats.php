<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Constants\DocumentStatus;
use DMS\Models\DocumentModel;
use DMS\Widgets\AWidget;

class DocumentStats extends AWidget {
    private DocumentModel $documentModel;

    public function __construct(DocumentModel $documentModel) {
        parent::__construct();
        
        $this->documentModel = $documentModel;
    }

    public function render() {
        $documentCount = $this->documentModel->getTotalDocumentCount();
        $shreddedCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED);
        $archivedCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED);
        $newCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::NEW);
        $waitingForArchivationCount = $this->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED);

        $this->add('Total documents', $documentCount);
        $this->add('Shredded documents', $shreddedCount);
        $this->add('Archived documents', $archivedCount);
        $this->add('New documents', $newCount);
        $this->add('Documents waiting for archivation', $waitingForArchivationCount);

        return parent::render();
    }
}

?>